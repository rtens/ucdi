<?php namespace rtens\ucdi\app\commands;

class MarkBrickLaid {

    /** @var string */
    private $brick;

    /**
     * @param string $brick
     */
    public function __construct($brick) {
        $this->brick = $brick;
    }

    /**
     * @return string
     */
    public function getBrick() {
        return $this->brick;
    }

}