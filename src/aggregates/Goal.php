<?php namespace rtens\ucdi\aggregates;

use rtens\ucdi\commands\CreateGoal;
use rtens\ucdi\events\GoalCreated;
use rtens\ucdi\events\GoalNotesChanged;

class Goal {

    public function handleCreateGoal(CreateGoal $command) {
        $goalId = GoalId::generate();
        $events = [new GoalCreated($goalId, $command->getName())];
        if ($command->getNotes()) {
            $events[] = new GoalNotesChanged($goalId, $command->getNotes());
        }
        return $events;
    }

}