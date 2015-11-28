<?php
namespace rtens\ucdi\app\events;

class GoalNameChanged {

    /** @var string */
    private $goalId;

    /** @var string */
    private $newName;

    /**
     * @param string $goalId
     * @param string $newName
     */
    public function __construct($goalId, $newName) {
        $this->goalId = $goalId;
        $this->newName = $newName;
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
    public function getNewName() {
        return $this->newName;
    }
}