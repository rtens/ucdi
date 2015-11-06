<?php namespace rtens\ucdi\app\events;

class GoalCreated {

    /** @var string */
    private $goalId;

    /** @var string */
    private $name;

    /**
     * @param string $goalId
     * @param string $name
     */
    public function __construct($goalId, $name) {
        $this->goalId = $goalId;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getGoalId() {
        return $this->goalId;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
}