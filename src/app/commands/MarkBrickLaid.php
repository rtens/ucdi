<?php namespace rtens\ucdi\app\commands;

class MarkBrickLaid {

    /** @var string */
    private $brickId;

    /**
     * @param string $brickId
     */
    public function __construct($brickId) {
        $this->brickId = $brickId;
    }

    /**
     * @return string
     */
    public function getBrickId() {
        return $this->brickId;
    }

}