<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;
use rtens\ucdi\app\GoalId;

class GoalCreated implements Event {

    /** @var GoalId */
    private $goalId;

    /** @var string */
    private $name;

    /**
     * @param GoalId $goalId
     * @param string $name
     */
    public function __construct(GoalId $goalId, $name) {
        $this->goalId = $goalId;
        $this->name = $name;
    }

    /**
     * @return \rtens\ucdi\es\AggregateId
     */
    public function aggregateId() {
        return $this->goalId;
    }

    function __toString() {
        return (string)$this->goalId;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
}