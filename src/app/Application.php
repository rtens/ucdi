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
use rtens\ucdi\app\commands\CancelGoal;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\LogEffort;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\commands\RateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\commands\UpdateGoal;
use rtens\ucdi\app\events\BrickCancelled;
use rtens\ucdi\app\events\BrickMarkedLaid;
use rtens\ucdi\app\events\BrickScheduled;
use rtens\ucdi\app\events\CalendarEventInserted;
use rtens\ucdi\app\events\EffortLogged;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalMarkedAchieved;
use rtens\ucdi\app\events\GoalNameChanged;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\GoalRated;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use rtens\ucdi\app\events\TaskMarkedCompleted;
use rtens\ucdi\app\model\Rating;
use rtens\ucdi\app\queries\ListGoals;
use rtens\ucdi\app\queries\ListMissedBricks;
use rtens\ucdi\app\queries\ReportEfforts;
use rtens\ucdi\app\queries\ShowGoal;
use rtens\ucdi\app\queries\ShowGoalOfBrick;
use rtens\ucdi\es\UidGenerator;
use rtens\ucdi\Settings;
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

    /** @var Settings */
    private $settings;

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
    /** @var CalendarEventInserted[][] */
    private $insertedCalendarEvents = [];
    private $cancelledBricks = [];
    private $cancelledGoals = [];
    /** @var EffortLogged[] */
    private $efforts = [];

    /** @var TaskAdded[] */
    private $tasks = [];

    public function __construct(UidGenerator $uid, Settings $settings, Calendar $calendar, Url $base, \DateTimeImmutable $now) {
        $this->uid = $uid;
        $this->calendar = $calendar;
        $this->base = $base;
        $this->now = $now;
        $this->settings = $settings;
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

        $events = [
            new BrickScheduled(
                $brickId,
                $command->getTask(),
                $command->getDescription(),
                $command->getStart(),
                $command->getDuration())
        ];

        if ($this->settings->calendarId) {
            $eventId = $this->calendar->insertEvent($this->settings->calendarId,
                $command->getDescription(), $command->getStart(), $command->getStart()->add($command->getDuration()), 'Show goal: ' . $this->base->appended('ShowGoalOfBrick')->withParameter('brick', $brickId) . "\n" .
                'Mark as laid: ' . $this->base->appended('MarkBrickLaid')->withParameter('brick', $brickId));
            $events[] = new CalendarEventInserted($brickId, $this->settings->calendarId, $eventId);
        }

        return $events;
    }

    public function handleMarkBrickLaid(MarkBrickLaid $command) {
        if (!isset($this->bricks[$command->getBrick()])) {
            throw new \Exception("Brick [{$command->getBrick()}] does not exist.");
        }
        if (isset($this->laidBricks[$command->getBrick()])) {
            $when = $this->laidBricks[$command->getBrick()];
            throw new \Exception("Brick [{$command->getBrick()}] was already laid [$when].");
        }

        $this->deleteInsertedCalendarEvents($command->getBrick());

        return [
            new BrickMarkedLaid($command->getBrick(), $this->now)
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
            $this->deleteInsertedCalendarEvents($brick['id']);
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
        $events = [
            new GoalMarkedAchieved($command->getGoal(), $this->now)
        ];

        foreach ($this->getIncompleteTasks($command->getGoal()) as $task) {
            $events = array_merge($events, $this->handleMarkTaskCompleted(new MarkTaskCompleted($task['id'])));
        }

        return $events;
    }

    public function handleRateGoal(RateGoal $command) {
        if (!isset($this->goals[$command->getGoal()])) {
            throw new \Exception("Goal [{$command->getGoal()}] does not exist.");
        }

        return [
            new GoalRated($command->getGoal(), $command->getRating())
        ];
    }

    public function handleCancelGoal(CancelGoal $command) {
        $goalId = $command->getGoal();

        if (!isset($this->goals[$goalId])) {
            throw new \Exception("Goal [$goalId] does not exist.");
        }
        if (isset($this->cancelledGoals[$goalId])) {
            throw new \Exception("Goal [$goalId] was already cancelled.");
        }
        if (isset($this->achievedGoals[$goalId])) {
            throw new \Exception("Goal [$goalId] is already achieved.");
        }

        $events = [
            new events\GoalCancelled($goalId, $this->now)
        ];

        foreach ($this->getIncompleteTasks($goalId) as $task) {
            $events = array_merge($events, $this->handleMarkTaskCompleted(new MarkTaskCompleted($task['id'])));
        }

        return $events;
    }

    public function handleLogEffort(LogEffort $command) {
        if (!isset($this->goalOfTask[$command->getTask()])) {
            throw new \Exception("Task [{$command->getTask()}] does not exist.");
        }

        if ($command->getEnd() <= $command->getStart()) {
            throw new \Exception('The end time must be after the start time');
        }

        return [
            new EffortLogged($command->getTask(), $command->getStart(), $command->getEnd(), $command->getComment())
        ];
    }

    public function handleUpdateGoal(UpdateGoal $command) {
        $goalId = $command->getGoal();
        if (!isset($this->goals[$goalId])) {
            throw new \Exception("The goal [$goalId] does not exist.");
        }

        $events = [];

        if ($command->getName() != $this->goals[$goalId]['name']) {
            $events[] = new GoalNameChanged($goalId, $command->getName());
        }

        $notes = $command->getNotes()->getContent();
        if (!isset($this->notes[$goalId]) || $notes != $this->notes[$goalId]) {
            $events[] = new GoalNotesChanged($goalId, $notes);
        }

        return $events;
    }

    public function executeReportEfforts(ReportEfforts $query) {
        $efforts = [];
        foreach ($this->efforts as $effort) {
            $efforts[] = [
                'id' => $this->goalOfTask[$effort->getTask()],
                'goal' => $this->goals[$this->goalOfTask[$effort->getTask()]]['name'],
                'task' => $this->tasks[$effort->getTask()]->getDescription(),
                'comment' => $effort->getComment(),
                'start' => $effort->getStart(),
                'end' => $effort->getEnd(),
                'duration' => $effort->getEnd()->diff($effort->getStart())
            ];
        }

        $efforts = array_filter($efforts, function ($e) use ($query) {
            $span = $query->getTimeSpan();
            $containsStart = $span && $span->contains($e['start']);
            $containsEnd = $span && $span->contains($e['end']);

            return (!$query->getGoal() || $e['id'] == $query->getGoal())
                && (!$span || ($containsStart && $containsEnd));
        });

        usort($efforts, function ($a, $b) {
            return $a['start'] < $b['start'] ? -1 : 1;
        });

        $total = 0;
        foreach ($efforts as $effort) {
            /** @var \DateTimeImmutable[] $effort */
            $total += $effort['end']->getTimestamp() - $effort['start']->getTimestamp();
        }
        return [
            'hours' => $total / 3600,
            'total' => (new \DateTime("@$total"))->diff(new \DateTime("@0")),
            'efforts' => $efforts
        ];
    }

    public function applyEffortLogged(EffortLogged $event) {
        $this->efforts[] = $event;
    }

    public function applyGoalCreated(GoalCreated $event) {
        $this->goals[$event->getGoalId()] = [
            'id' => $event->getGoalId(),
            'name' => $event->getName()
        ];
    }

    public function applyTaskAdded(TaskAdded $event) {
        $this->tasks[$event->getTaskId()] = $event;
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

    public function applyGoalNameChanged(GoalNameChanged $event) {
        $this->goals[$event->getGoalId()]['name'] = $event->getNewName();
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
        $this->insertedCalendarEvents[$event->getBrickId()][] = $event;
    }

    public function applyBrickCancelled(BrickCancelled $event) {
        $this->cancelledBricks[$event->getBrickId()] = true;
    }

    public function applyGoalCancelled(events\GoalCancelled $event) {
        $this->cancelledGoals[$event->getGoalId()] = $event->getWhen();
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
            return !isset($this->cancelledGoals[$goal['id']]) && $query->isAchieved() == isset($this->achievedGoals[$goal['id']]);
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
            if (isset($this->achievedGoals[$goalId]) || isset($this->cancelledGoals[$goalId])) {
                continue;
            } else if (isset($this->ratings[$goalId])) {
                $rating = $this->ratings[$goalId];
            } else {
                $rating = new Rating(0, 0);
            }

            $incompleteTasks = $this->getIncompleteTasks($goalId);
            if ($incompleteTasks) {
                if (!$this->getNextBrick($goalId)) {
                    $color = Color::PURPLE();
                } else {
                    $color = Color::GREEN();
                }
                $size = min(3, 0.5 + count($incompleteTasks) / 4);
            } else {
                $color = Color::RED();
                $size = 2;
            }

            $data[] = new ScatterDataSet(
                [
                    new ScatterDataPoint($rating->getUrgency(), $rating->getImportance(), $size)
                ],
                $this->goals[$goalId]['name'],
                $color);
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
            'rating' => isset($this->ratings[$goalId]) ? $this->ratings[$goalId] : null,
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
                && !isset($this->cancelledBricks[$brick->getBrickId()])
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

    private function deleteInsertedCalendarEvents($brickId) {
        foreach ($this->insertedCalendarEvents[$brickId] as $event) {
            $calendarId = $event->getCalendarId() ?: $this->settings->calendarId; // Older events don't have a calendarId
            $this->calendar->deleteEvent($calendarId, $event->getCalendarEventId());
        }
    }
}