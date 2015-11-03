<?php namespace rtens\ucdi\app\queries;

class ShowGoal {

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