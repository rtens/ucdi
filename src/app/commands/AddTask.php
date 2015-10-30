<?php namespace rtens\ucdi\app\commands;

class AddTask {

    /** @var string */
    private $goal;

    /** @var string */
    private $description;

    /** @var null|string */
    private $dependency;

    /**
     * @param string $goal
     * @param string $description
     * @param null|string $dependency Task that needs to be completed before this task can be completed
     */
    public function __construct($goal, $description, $dependency = null) {
        $this->goal = $goal;
        $this->description = $description;
        $this->dependency = $dependency;
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

    /**
     * @return null|string
     */
    public function getDependency() {
        return $this->dependency;
    }

}