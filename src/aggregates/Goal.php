<?php namespace rtens\ucdi\aggregates;

use rtens\ucdi\commands\CreateGoal;
use rtens\ucdi\events\GoalCreated;

class Goal {

    public function handleCreateGoal(CreateGoal $command) {
        return [new GoalCreated(GoalId::generate(), $command->getName())];
    }

}