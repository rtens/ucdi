<?php namespace rtens\ucdi\app;

use rtens\domin\delivery\web\renderers\charting\charts\ScatterChart;
use rtens\domin\delivery\web\renderers\charting\data\ScatterDataPoint;
use rtens\domin\delivery\web\renderers\charting\data\ScatterDataSet;
use rtens\domin\delivery\web\renderers\dashboard\types\ActionPanel;
use rtens\domin\delivery\web\renderers\dashboard\types\Column;
use rtens\domin\delivery\web\renderers\dashboard\types\Dashboard;
use rtens\domin\delivery\web\renderers\dashboard\types\Row;
use rtens\domin\execution\RedirectResult;
use rtens\domin\parameters\Color;
use rtens\domin\parameters\Html;
use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\commands\RateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickCancelled;
use rtens\ucdi\app\events\BrickMarkedLaid;
use rtens\ucdi\app\events\BrickScheduled;
use rtens\ucdi\app\events\CalendarEventInserted;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalMarkedAchieved;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\GoalRated;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use rtens\ucdi\app\events\TaskMarkedCompleted;
use rtens\ucdi\app\model\Rating;
use rtens\ucdi\app\queries\ListGoals;
use rtens\ucdi\app\queries\ListMissedBricks;
use rtens\ucdi\app\queries\ShowGoal;
use rtens\ucdi\app\queries\ShowGoalOfBrick;
use rtens\ucdi\es\UidGenerator;
use watoki\curir\protocol\Url;

class Application {

    /** @var UidGenerator */
    private $uid;

    /** @var Calendar */
    private $calendar;

    /** @var \watoki\curir\protocol\Url */
    private $base;

    /** @var \DateTimeImmutable */
    private $now;

    private $goals = [];
    private $goalOfTask = [];
    private $notes = [];
    private $tasksOfGoals = [];
    private $bricksOfTasks = [];
    private $laidBricks = [];
    private $completedTasks = [];
    private $achievedGoals = [];
    /** @var array|Rating[] */
    private $ratings = [];
    /** @var array|BrickScheduled[] */
    private $bricks = [];
    private $calendarEventIds = [];
    private $cancelledBricks = [];

    public function __construct(UidGenerator $uid, Calendar $calendar, Url $base, \DateTimeImmutable $now) {
        $this->uid = $uid;
        $this->calendar = $calendar;
        $this->base = $base;
        $this->now = $now;
    }

    public function handleCreateGoal(CreateGoal $command) {
        $goalId = $this->uid->generate('Goal');

        $events = [
            new GoalCreated($goalId, $command->getName())
        ];

        if ($command->getNotesContent()) {
            $events[] = new GoalNotesChanged($goalId, $command->getNotesContent());
        }

        if ($command->getRating()) {
            $events[] = new GoalRated($goalId, $command->getRating());
        }

        foreach ($command->getAllTasks() as $taskDescription) {
            $events[] = new TaskAdded($this->uid->generate('Task'), $goalId, $taskDescription);
        }

        return $events;
    }

    public function handleAddTask(AddTask $command) {
        if (!isset($this->goals[$command->getGoal()])) {
            throw new \Exception("Goal [{$command->getGoal()}] does not exist.");
        }

        $taskId = $this->uid->generate('Task');

        $events = [
            new TaskAdded($taskId, $command->getGoal(), $command->getDescription())
        ];

        if ($command->getDependency()) {
            $events[] = new TaskMadeDependent($taskId, $command->getDependency());
        }

        return $events;
    }

    public function handleScheduleBrick(ScheduleBrick $command) {
        if (!isset($this->goalOfTask[$command->getTask()])) {
            throw new \Exception("Task [{$command->getTask()}] does not exist.");
        }
        if ($command->getStart() < $this->now) {
            throw new \Exception('Cannot schedule brick in the past');
        }
        $brickId = $this->uid->generate('Brick');

        $eventId = $this->calendar->insertEvent(
            $command->getDescription(),
            $command->getStart(),
            $command->getStart()->add($command->getDuration()),
            'Mark as laid: ' . $this->base->appended('MarkBrickLaid')->withParameter('brickId', $brickId));

        return [
            new BrickScheduled(
                $brickId,
                $command->getTask(),
                $command->getDescription(),
                $command->getStart(),
                $command->getDuration()),
            new CalendarEventInserted($brickId, $eventId)
        ];
    }

    public function handleMarkBrickLaid(MarkBrickLaid $command) {
        if (!isset($this->bricks[$command->getBrickId()])) {
            throw new \Exception("Brick [{$command->getBrickId()}] does not exist.");
        }
        if (isset($this->laidBricks[$command->getBrickId()])) {
            $when = $this->laidBricks[$command->getBrickId()];
            throw new \Exception("Brick [{$command->getBrickId()}] was already laid [$when].");
        }
        return [
            new BrickMarkedLaid($command->getBrickId(), $this->now)
        ];
    }

    public function handleMarkTaskCompleted(MarkTaskCompleted $command) {
        if (!isset($this->goalOfTask[$command->getTask()])) {
            throw new \Exception("Task [{$command->getTask()}] does not exist.");
        }
        if (isset($this->completedTasks[$command->getTask()])) {
            $when = $this->completedTasks[$command->getTask()];
            throw new \Exception("Task [{$command->getTask()}] was already completed [$when].");
        }

        $events = [
            new TaskMarkedCompleted($command->getTask(), $this->now)
        ];

        foreach ($this->getUpcomingUnlaidBricks($command->getTask()) as $brick) {
            $events[] = new BrickCancelled($brick['id']);
            $this->calendar->deleteEvent($this->calendarEventIds[$brick['id']]);
        }

        return $events;
    }

    public function handleMarkGoalAchieved(MarkGoalAchieved $command) {
        if (!isset($this->goals[$command->getGoal()])) {
            throw new \Exception("Goal [{$command->getGoal()}] does not exist.");
        }
        if (isset($this->achievedGoals[$command->getGoal()])) {
            $when = $this->achievedGoals[$command->getGoal()];
            throw new \Exception("Goal [{$command->getGoal()}] was already achieved [$when].");
        }
        return [
            new GoalMarkedAchieved($command->getGoal(), $this->now)
        ];
    }

    public function handleRateGoal(RateGoal $command) {
        if (!isset($this->goals[$command->getGoal()])) {
            throw new \Exception("Goal [{$command->getGoal()}] does not exist.");
        }

        return [
            new GoalRated($command->getGoal(), $command->getRating())
        ];
    }

    public function applyGoalCreated(GoalCreated $event) {
        $this->goals[$event->getGoalId()] = [
            'id' => $event->getGoalId(),
            'name' => $event->getName()
        ];
    }

    public function applyTaskAdded(TaskAdded $event) {
        $this->goalOfTask[$event->getTaskId()] = $event->getGoalId();
        $this->tasksOfGoals[$event->getGoalId()][] = [
            'id' => $event->getTaskId(),
            'description' => $event->getDescription()
        ];
    }

    public function applyBrickScheduled(BrickScheduled $event) {
        $this->bricks[$event->getBrickId()] = $event;
        $this->bricksOfTasks[$event->getTaskId()][] = [
            'id' => $event->getBrickId(),
            'description' => $event->getDescription(),
            'start' => $event->getStart()->format('Y-m-d H:i'),
            'duration' => $event->getDuration()->format('%H:%I'),
        ];

        if ($event->getStart() < $this->now) {
            return;
        }

    }

    public function applyGoalNotesChanged(GoalNotesChanged $event) {
        $this->notes[$event->getGoalId()] = $event->getNotes();
    }

    public function applyBrickMarkedLaid(BrickMarkedLaid $event) {
        $this->laidBricks[$event->getBrickId()] = $event->getWhen()->format('Y-m-d H:i');
    }

    public function applyTaskMarkedCompleted(TaskMarkedCompleted $event) {
        $this->completedTasks[$event->getTaskId()] = $event->getWhen()->format('Y-m-d H:i');
    }

    public function applyGoalMarkedAchieved(GoalMarkedAchieved $event) {
        $this->achievedGoals[$event->getGoalId()] = $event->getWhen()->format('Y-m-d H:i');
    }

    public function applyGoalRated(GoalRated $event) {
        $this->ratings[$event->getGoal()] = $event->getRating();
    }

    public function applyCalendarEventInserted(CalendarEventInserted $event) {
        $this->calendarEventIds[$event->getBrickId()] = $event->getCalendarEventId();
    }

    public function applyBrickCancelled(BrickCancelled $event) {
        $this->cancelledBricks[$event->getBrickId()] = true;
    }

    public function executeListGoals(ListGoals $query) {
        $goals = array_map(function ($goal) use ($query) {
            return array_merge($goal, [
                'rating' => isset($this->ratings[$goal['id']]) ? (string)$this->ratings[$goal['id']] : null,
                'nextBrick' => $this->getNextBrick($goal['id']),
                'tasks' => array_map(function ($task) {
                    return $task['description'];
                }, $this->getIncompleteTasks($goal['id'])) ?: null
            ]);
        }, array_filter(array_values($this->goals), function ($goal) use ($query) {
            return $query->isAchieved() == isset($this->achievedGoals[$goal['id']]);
        }));

        if ($query->isOnlyBrickLess()) {
            $goals = array_filter($goals, function ($goal) {
                return !$goal['nextBrick'];
            });
            $goals = array_map(function ($goal) {
                unset($goal['nextBrick']);
                return $goal;
            }, $goals);
        }

        usort($goals, function ($a, $b) {
            $ratingA = isset($this->ratings[$a['id']]) ? $this->ratings[$a['id']] : new Rating(0, 0);
            $ratingB = isset($this->ratings[$b['id']]) ? $this->ratings[$b['id']] : new Rating(0, 0);
            if ($ratingA->getQuadrant() == $ratingB->getQuadrant()) {
                if ($ratingA->getUrgency() == $ratingB->getUrgency()) {
                    return strcmp($a['name'], $b['name']);
                }
                return $ratingB->getUrgency() - $ratingA->getUrgency();
            }
            return $ratingA->getQuadrant() - $ratingB->getQuadrant();
        });

        return $goals;
    }

    public function executePlotGoals() {
        return new ScatterChart($this->getRatingScatterData());
    }

    private function getRatingScatterData() {
        $data = [
            new ScatterDataSet([
                new ScatterDataPoint(0, 0, 0.1),
                new ScatterDataPoint(10, 10, 0.1),
            ], '', Color::fromHex('#ffffff'))
        ];
        foreach ($this->goals as $goalId => $goal) {
            if (isset($this->achievedGoals[$goalId])) {
                continue;
            } else if (isset($this->ratings[$goalId])) {
                $rating = $this->ratings[$goalId];
            } else {
                $rating = new Rating(0, 0);
            }

            $data[] = new ScatterDataSet(
                [
                    new ScatterDataPoint($rating->getUrgency(), $rating->getImportance(), 2)
                ],
                $this->goals[$goalId]['name'],
                $this->getNextBrick($goalId) ? Color::GREEN() : Color::RED());
        }
        return $data;
    }

    public function executeShowGoalOfBrick(ShowGoalOfBrick $query) {
        $taskId = $this->bricks[$query->getBrick()]->getTaskId();
        $goalId = $this->goalOfTask[$taskId];
        return new RedirectResult('ShowGoal', ['goal' => $goalId]);
    }

    public function executeShowGoal(ShowGoal $query) {
        if (!isset($this->goals[$query->getGoal()])) {
            throw new \Exception("Goal [{$query->getGoal()}] does not exist.");
        }
        $goalId = $query->getGoal();
        return array_merge($this->goals[$goalId], [
            'rating' => isset($this->ratings[$goalId]) ? (string)$this->ratings[$goalId] : null,
            'notes' => isset($this->notes[$goalId]) ? new Html($this->notes[$goalId]) : null,
            'incompleteTasks' => $this->getIncompleteTasksWithBricks($goalId)
        ]);
    }

    private function getIncompleteTasksWithBricks($goalId) {
        return array_values(array_map(function ($task) {
            return array_merge($task, [
                'bricks' => $this->getUpcomingUnlaidBricks($task['id'])
            ]);
        }, $this->getIncompleteTasks($goalId)));
    }

    private function getIncompleteTasks($goalId) {
        $tasksOfGoal = isset($this->tasksOfGoals[$goalId]) ? $this->tasksOfGoals[$goalId] : [];

        $incompleteTasks = array_filter($tasksOfGoal, function ($task) {
            return !isset($this->completedTasks[$task['id']]);
        });
        return $incompleteTasks;
    }

    public function executeListMissedBricks(ListMissedBricks $query) {
        return $this->listActiveBricks(function (BrickScheduled $brick) use ($query) {
            return !isset($this->laidBricks[$brick->getBrickId()])
            && $brick->getStart() < $this->now
                && (!$query->getMaxAge() || $brick->getStart()->add($query->getMaxAge()) >= $this->now);
        });
    }

    public function executeListUpcomingBricks() {
        return $this->listActiveBricks(function (BrickScheduled $brick) {
            return !isset($this->laidBricks[$brick->getBrickId()]) && $brick->getStart() >= $this->now;
        });
    }

    public function executeShowBrickStatistics() {
        $current = 0;
        $longest = 0;

        $bricks = $this->listActiveBricks(function (BrickScheduled $brick) {
            return $brick->getStart()->add($brick->getDuration()) < $this->now;
        });

        foreach ($bricks as $brick) {
            if (isset($this->laidBricks[$brick['id']])) {
                $current++;
            } else {
                $current = 0;
            }

            if ($current > $longest) {
                $longest = $current;
            }
        }

        return [
            'total' => count($bricks),
            'laid' => count($this->laidBricks),
            'currentStreak' => $current,
            'longestStreak' => $longest
        ];
    }

    public function executeShowDashboard() {
        return new Dashboard([
            new Row([
                new Column([
                    new ActionPanel('PlotGoals'),
                    (new ActionPanel('ListGoals', ['onlyBrickLess' => true]))
                        ->setMaxHeight('20em'),
                ], 6),
                new Column([
                    new ActionPanel('ShowBrickStatistics'),
                    new ActionPanel('ListMissedBricks', ['maxAge' => ['h' => 24]]),
                    (new ActionPanel('ListUpcomingBricks'))
                        ->setMaxHeight('20em'),
                ], 6),
            ]),
        ]);
    }

    private function listActiveBricks(callable $filter = null) {
        $bricks = [];
        foreach ($this->bricks as $brick) {
            $isCancelled = isset($this->cancelledBricks[$brick->getBrickId()]);
            $passesFilter = !$filter || $filter($brick);

            if (!$isCancelled && $passesFilter) {
                $bricks[] = [
                    'id' => $brick->getBrickId(),
                    'description' => $brick->getDescription(),
                    'start' => $brick->getStart()->format('Y-m-d H:i')
                ];
            }
        }

        usort($bricks, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        return $bricks;
    }

    private function getNextBrick($goalId) {
        /** @var BrickScheduled|null $next */
        $next = null;

        $taskIds = [];
        if (isset($this->tasksOfGoals[$goalId])) {
            foreach ($this->tasksOfGoals[$goalId] as $task) {
                $taskIds[] = $task['id'];
            }
        }

        foreach ($this->bricks as $brick) {
            if (
                $brick->getStart()->add($brick->getDuration()) > $this->now
                && !isset($this->laidBricks[$brick->getBrickId()])
                && (!$next || $brick->getStart() < $next->getStart())
                && in_array($brick->getTaskId(), $taskIds)
            ) {

                $next = $brick;
            }
        }

        return $next ? ($next->getDescription() . ' @' . $next->getStart()->format('Y-m-d H:i')) : null;
    }

    private function getUpcomingUnlaidBricks($taskId) {
        if (!isset($this->bricksOfTasks[$taskId])) {
            return [];
        }
        $bricks = array_filter($this->bricksOfTasks[$taskId], function ($brick) {
            return new \DateTimeImmutable($brick['start']) >= $this->now && !isset($this->laidBricks[$brick['id']]);
        });
        usort($bricks, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });
        return array_values($bricks);
    }
}