<?php namespace rtens\ucdi\app;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickScheduled;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use rtens\ucdi\es\UidGenerator;

class Application {

    /** @var UidGenerator */
    private $uid;

    /** @var Calendar */
    private $calendar;

    /** @var Time */
    private $time;

    private $goals = [];
    private $goalOfTask = [];
    private $nextBrick = [];

    public function __construct(UidGenerator $uid, Calendar $calendar, Time $time) {
        $this->uid = $uid;
        $this->calendar = $calendar;
        $this->time = $time;
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
        if ($command->getStart() < $this->time->now()) {
            throw new \Exception('Cannot schedule brick in the past');
        }
        $brickId = $this->uid->generate('Brick');

        $this->calendar->insertEvent(
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
                $command->getDuration())
        ];
    }

    public function applyGoalCreated(GoalCreated $event) {
        $this->goals[$event->getGoalId()] = [
            'id' => $event->getGoalId(),
            'name' => $event->getName(),
            'nextBrick' => null
        ];
    }

    public function applyTaskAdded(TaskAdded $event) {
        $this->goalOfTask[$event->getTaskId()] = $event->getGoalId();
    }

    public function applyBrickScheduled(BrickScheduled $event) {
        if ($event->getStart() < $this->time->now()) {
            return;
        }

        $goalId = $this->goalOfTask[$event->getTaskId()];
        if (isset($this->nextBrick[$goalId]) && $event->getStart() > $this->nextBrick[$goalId]) {
            return;
        }

        $this->nextBrick[$goalId] = $event->getStart();
        $this->goals[$goalId]['nextBrick'] = $event->getDescription() . ' @' . $event->getStart()->format('Y-m-d H:i');
    }

    public function executeListGoals() {
        return array_values($this->goals);
    }

}