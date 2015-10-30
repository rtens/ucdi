<?php namespace rtens\ucdi\app\events;


class GoalNotesChanged {

    /** @var string */
    private $goalId;

    /** @var string */
    private $notes;

    /**
     * @param string $goalId
     * @param string $notes
     */
    public function __construct($goalId, $notes) {
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