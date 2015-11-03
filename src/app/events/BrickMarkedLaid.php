<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;

class BrickMarkedLaid extends Event {

    /** @var string */
    private $brickId;

    /** @var \DateTimeImmutable */
    private $when;

    /**
     * @param string $brickId
     */
    public function __construct($brickId, \DateTimeImmutable $when) {
        parent::__construct();
        $this->brickId = $brickId;
        $this->when = $when;
    }

    /**
     * @return string
     */
    public function getBrickId() {
        return $this->brickId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getWhen() {
        return $this->when;
    }
}