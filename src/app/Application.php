<?php namespace rtens\ucdi\app;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickMarkedLaid;
use rtens\ucdi\app\events\BrickScheduled;
use rtens\ucdi\app\events\CalendarEventInserted;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalMarkedAchieved;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use rtens\ucdi\app\events\TaskMarkedCompleted;
use rtens\ucdi\app\queries\ShowGoal;
use rtens\ucdi\es\UidGenerator;

class Application {

    /** @var UidGenerator */
    private $uid;

    /** @var Calendar */
    private $calendar;

    /** @var \DateTimeImmutable */
    private $now;

    private $goals = [];
    private $goalOfTask = [];
    private $nextBrick = [];
    private $notes = [];
    private $tasksOfGoals = [];
    private $bricksOfTasks = [];
    private $laidBricks = [];
    private $completedTasks = [];
    private $achievedGoals = [];
    private $bricks = [];

    public function __construct(UidGenerator $uid, Calendar $calendar, \DateTimeImmutable $now) {
        $this->uid = $uid;
        $this->calendar = $calendar;
        $this->now = $now;
    }

    public function handleCreateGoal(CreateGoal $command) {
        $goalId = $this->uid->generate('Goal');

        $events = [
            new GoalCreated($goalId, $command->getName())
        ];

        if ($command->getNotes()) {
            $events[] = new GoalNotesChanged($goalId, $command->getNotes());
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
            'Link to ' . $brickId);

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
        $this->bricks[$event->getBrickId()] = true;
        $this->bricksOfTasks[$event->getTaskId()][] = [
            'description' => $event->getDescription(),
            'start' => $event->getStart()->format('Y-m-d H:i'),
            'duration' => $event->getDuration()->format('%H:%I'),
        ];

        if ($event->getStart() < $this->now) {
            return;
        }

        $goalId = $this->goalOfTask[$event->getTaskId()];
        if (isset($this->nextBrick[$goalId]) && $event->getStart() > $this->nextBrick[$goalId]['start']) {
            return;
        }

        $this->nextBrick[$goalId] = [
            'description' => $event->getDescription(),
            'start' => $event->getStart()
        ];
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

    public function executeListGoals() {
        return array_map(function ($goal) {
            return array_merge($goal, [
                'nextBrick' => $this->getNextBrick($goal['id'])
            ]);
        }, array_values($this->goals));
    }

    public function executeShowGoal(ShowGoal $query) {
        if (!isset($this->goals[$query->getGoal()])) {
            throw new \Exception("Goal [{$query->getGoal()}] does not exist.");
        }
        $goalId = $query->getGoal();
        return array_merge($this->goals[$goalId], [
            'notes' => isset($this->notes[$goalId]) ? $this->notes[$goalId] : null,
            'tasks' => $this->getTasksWithBricks($goalId)
        ]);
    }

    private function getNextBrick($goalId) {
        if (!isset($this->nextBrick[$goalId])) {
            return null;
        }

        $brick = $this->nextBrick[$goalId];
        /** @var \DateTime $start */
        $start = $brick['start'];
        return $brick['description'] . ' @' . $start->format('Y-m-d H:i');
    }

    private function getTasksWithBricks($goalId) {
        if (!isset($this->tasksOfGoals[$goalId])) {
            return [];
        }

        return array_map(function ($task) {
            return array_merge($task, [
                'bricks' => $this->getBricks($task['id'])
            ]);
        }, $this->tasksOfGoals[$goalId]);
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