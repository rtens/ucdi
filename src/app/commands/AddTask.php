<?php namespace rtens\ucdi\app\commands;

class AddTask {

    /** @var string */
    private $goal;

    /** @var string */
    private $description;

    /**
     * @param string $goal
     * @param string $description
     */
    public function __construct($goal, $description) {
        $this->goal = $goal;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getGoal() {
        return $this->goal;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

}