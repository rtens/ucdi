<?php namespace rtens\ucdi\es;

class Event {

    /** @var \DateTimeImmutable */
    private $occurred;

    /** @var object */
    private $event;

    /**
     * @param object $event
     * @param \DateTimeImmutable $occurred
     */
    public function __construct($event, \DateTimeImmutable $occurred) {
        $this->occurred = $occurred;
        $this->event = $event;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getOccurred() {
        return $this->occurred;
    }

    /**
     * @return object
     */
    public function getEvent() {
        return $this->event;
    }
}