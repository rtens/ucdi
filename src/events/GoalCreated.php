<?php namespace rtens\ucdi\events;

use rtens\ucdi\aggregates\GoalId;
use rtens\ucdi\app\Event;

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
     * @return \rtens\ucdi\app\AggregateId
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