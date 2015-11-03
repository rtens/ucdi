<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;

class GoalCreated extends Event {

    /** @var string */
    private $goalId;

    /** @var string */
    private $name;

    /**
     * @param string $goalId
     * @param string $name
     */
    public function __construct($goalId, $name) {
        parent::__construct();
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