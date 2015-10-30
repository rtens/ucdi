<?php namespace rtens\ucdi\app;

use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;

class Goal {

    public function handleCreateGoal(CreateGoal $command) {
        $goalId = \rtens\ucdi\app\GoalId::generate();
        $events = [new GoalCreated($goalId, $command->getName())];
        if ($command->getNotes()) {
            $events[] = new GoalNotesChanged($goalId, $command->getNotes());
        }
        return $events;
    }

}