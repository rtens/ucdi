<?php namespace rtens\ucdi\app\commands;

class MarkGoalAchieved {

    /** @var string */
    private $goal;

    /**
     * @param string $goal
     */
    public function __construct($goal) {
        $this->goal = $goal;
    }

    public function getGoal() {
        return $this->goal;
    }
}