<?php namespace rtens\ucdi\es;

class Event {

    /** @var \DateTimeImmutable */
    private $created;

    public function __construct() {
        $this->created = new \DateTimeImmutable();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated() {
        return $this->created;
    }
}