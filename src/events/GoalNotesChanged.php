<?php namespace rtens\ucdi\events;

use rtens\ucdi\aggregates\GoalId;
use rtens\ucdi\app\Event;

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
     * @return \rtens\ucdi\app\AggregateId
     */
    public function aggregateId() {
        return $this->goalId;
    }

    public function getNotes() {
        return $this->notes;
    }
}