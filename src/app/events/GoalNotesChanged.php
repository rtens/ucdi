<?php namespace rtens\ucdi\app\events;


use rtens\ucdi\es\Event;

class GoalNotesChanged extends Event {

    /** @var string */
    private $goalId;

    /** @var string */
    private $notes;

    /**
     * @param string $goalId
     * @param string $notes
     */
    public function __construct($goalId, $notes) {
        parent::__construct();
        $this->goalId = $goalId;
        $this->notes = $notes;
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
    public function getNotes() {
        return $this->notes;
    }
}