<?php namespace rtens\ucdi\app\commands;

class CancelGoal {

    /** @var string */
    private $goal;

    /**
     * @param string $goal
     */
    public function __construct($goal) {
        $this->goal = $goal;
    }

    /**
     * @return string
     */
    public function getGoal() {
        return $this->goal;
    }
}