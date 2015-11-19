<?php namespace rtens\ucdi\app\events;

class EffortLogged {

    /** @var string */
    private $task;

    /** @var \DateTimeImmutable */
    private $start;

    /** @var \DateTimeImmutable */
    private $end;

    /** @var null|string */
    private $comment;

    /**
     * @param string $task
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param null|string $comment
     */
    public function __construct($task, \DateTimeImmutable $start, \DateTimeImmutable $end, $comment = null) {
        $this->task = $task;
        $this->start = $start;
        $this->end = $end;
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getTask() {
        return $this->task;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStart() {
        return $this->start;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEnd() {
        return $this->end;
    }

    /**
     * @return null|string
     */
    public function getComment() {
        return $this->comment;
    }
}