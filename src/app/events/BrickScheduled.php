<?php namespace rtens\ucdi\app\events;

class BrickScheduled {

    /** @var string */
    private $brickId;

    /** @var string */
    private $taskId;

    /** @var string */
    private $description;

    /** @var \DateTimeImmutable */
    private $start;

    /** @var \DateInterval */
    private $duration;

    /**
     * @param string $brickId
     * @param string $taskId
     * @param string $description
     * @param \DateTimeImmutable $start
     * @param \DateInterval $duration
     */
    public function __construct($brickId, $taskId, $description, \DateTimeImmutable $start, \DateInterval $duration) {
        $this->taskId = $taskId;
        $this->description = $description;
        $this->start = $start;
        $this->duration = $duration;
        $this->brickId = $brickId;
    }

    /**
     * @return string
     */
    public function getBrickId() {
        return $this->brickId;
    }

    /**
     * @return string
     */
    public function getTaskId() {
        return $this->taskId;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStart() {
        return $this->start;
    }

    /**
     * @return \DateInterval
     */
    public function getDuration() {
        return $this->duration;
    }
}