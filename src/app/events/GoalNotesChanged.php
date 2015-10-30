<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;
use rtens\ucdi\app\GoalId;

class GoalNotesChanged implements Event {

    /** @var GoalId */
    private $goalId;

    /** @var string */
    private $notes;

    /**
     * @param GoalId $goalId
     * @param string $notes
     */
    public function __construct(GoalId $goalId, $notes) {
        $this->goalId = $goalId;
        $this->notes = $notes;
    }

    /**
     * @return \rtens\ucdi\es\AggregateId
     */
    public function aggregateId() {
        return $this->goalId;
    }

    public function getNotes() {
        return $this->notes;
    }
}