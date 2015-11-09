<?php namespace rtens\ucdi\app;

use rtens\domin\parameters\Html;
use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\commands\RateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
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
use rtens\ucdi\app\queries\ListGoals;
use rtens\ucdi\app\queries\ShowGoal;
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
    private $ratings = [];
    /** @var array|BrickScheduled[] */
    private $bricks = [];

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
        return [
            new TaskMarkedCompleted($command->getTask(), $this->now)
        ];
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

    public function executeListGoals(ListGoals $query) {
        $goals = array_map(function ($goal) {
            return array_merge($goal, [
                'rating' => isset($this->ratings[$goal['id']]) ? (string)$this->ratings[$goal['id']] : null,
                'nextBrick' => $this->getNextBrick($goal['id'])
            ]);
        }, array_values($this->goals));

        if ($query->isOnlyBrickLess()) {
            $goals = array_filter($goals, function ($goal) {
                return !$goal['nextBrick'];
            });
        }

        return $goals;
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
        if (!isset($this->tasksOfGoals[$goalId])) {
            return [];
        }

        return array_values(array_map(function ($task) {
            return array_merge($task, [
                'bricks' => $this->getBricks($task['id'])
            ]);
        }, array_filter($this->tasksOfGoals[$goalId], function ($task) {
            return !isset($this->completedTasks[$task['id']]);
        })));
    }

    public function executeListMissedBricks() {
        return $this->listBricks(function (BrickScheduled $brick) {
            return !isset($this->laidBricks[$brick->getBrickId()]) && $brick->getStart() < $this->now;
        });
    }

    public function executeListUpcomingBricks() {
        return $this->listBricks(function (BrickScheduled $brick) {
            return !isset($this->laidBricks[$brick->getBrickId()]) && $brick->getStart() >= $this->now;
        });
    }

    private function listBricks(callable $filter) {
        $missedBricks = [];
        foreach ($this->bricks as $brick) {
            if ($filter($brick)) {
                $missedBricks[] = [
                    'id' => $brick->getBrickId(),
                    'description' => $brick->getDescription(),
                    'start' => $brick->getStart()->format('Y-m-d H:i')
                ];
            }
        }

        usort($missedBricks, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });

        return $missedBricks;
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
                $brick->getStart() > $this->now
                && !isset($this->laidBricks[$brick->getBrickId()])
                && (!$next || $brick->getStart() < $next->getStart())
                && in_array($brick->getTaskId(), $taskIds)
            ) {

                $next = $brick;
            }
        }

        return $next ? ($next->getDescription() . ' @' . $next->getStart()->format('Y-m-d H:i')) : null;
    }

    private function getBricks($taskId) {
        if (!isset($this->bricksOfTasks[$taskId])) {
            return [];
        }
        $bricks = array_filter($this->bricksOfTasks[$taskId], function ($brick) {
            return new \DateTimeImmutable($brick['start']) >= $this->now;
        });
        usort($bricks, function ($a, $b) {
            return strcmp($a['start'], $b['start']);
        });
        return array_values($bricks);
    }
}