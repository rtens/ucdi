<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;

class GoalMarkedAchieved extends Event {

    /** @var string */
    private $goalId;

    /** @var \DateTimeImmutable */
    private $when;

    /**
     * @param string $goalId
     * @param \DateTimeImmutable $when
     */
    public function __construct($goalId, \DateTimeImmutable $when) {
        parent::__construct();
        $this->goalId = $goalId;
        $this->when = $when;
    }

    /**
     * @return string
     */
    public function getGoalId() {
        return $this->goalId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getWhen() {
        return $this->when;
    }
}