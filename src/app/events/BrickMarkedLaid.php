<?php namespace rtens\ucdi\app\events;

class BrickMarkedLaid {

    /** @var string */
    private $brickId;

    /** @var \DateTimeImmutable */
    private $when;

    /**
     * @param string $brickId
     */
    public function __construct($brickId, \DateTimeImmutable $when) {
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