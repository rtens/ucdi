<?php namespace rtens\ucdi\app\commands;

class ScheduleBrick {

    /** @var \DateTimeImmutable */
    private $start;

    /** @var string */
    private $task;

    /** @var string */
    private $description;

    /** @var \DateInterval */
    private $duration;

    /**
     * @param string $task
     * @param string $description
     * @param \DateTimeImmutable $start
     * @param \DateInterval $duration
     */
    public function __construct($task, $description, \DateTimeImmutable $start, \DateInterval $duration) {
        $this->start = $start;
        $this->task = $task;
        $this->description = $description;
        $this->duration = $duration;
    }

    public function getStart() {
        return $this->start;
    }

    public function getTask() {
        return $this->task;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDuration() {
        return $this->duration;
    }
}