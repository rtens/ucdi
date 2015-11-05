<?php namespace rtens\ucdi\es;

class Event {

    /** @var \DateTimeImmutable */
    private $created;

    public function __construct(\DateTimeImmutable $created = null) {
        $this->created = $created ?: new \DateTimeImmutable();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated() {
        return $this->created;
    }
}