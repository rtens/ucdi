<?php namespace rtens\ucdi\app;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\es\UidGenerator;

class Application {

    /** @var UidGenerator */
    private $uid;

    /**
     * @param UidGenerator $uid
     */
    public function __construct(UidGenerator $uid) {
        $this->uid = $uid;
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
        return [
            new TaskAdded($this->uid->generate('Task'), $command->getGoal(), $command->getDescription())
        ];
    }

}