<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;

class TaskMarkedCompleted extends Event {

    /** @var string */
    private $taskId;

    /** @var \DateTimeImmutable */
    private $when;

    /**
     * @param string $taskId
     * @param \DateTimeImmutable $when
     */
    public function __construct($taskId, \DateTimeImmutable $when) {
        parent::__construct();
        $this->taskId = $taskId;
        $this->when = $when;
    }

    /**
     * @return string
     */
    public function getTaskId() {
        return $this->taskId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getWhen() {
        return $this->when;
    }
}