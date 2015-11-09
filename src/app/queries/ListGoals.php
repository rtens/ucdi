<?php namespace rtens\ucdi\app\queries;

class ListGoals {

    /** @var bool */
    private $onlyBrickLess;

    /**
     * @param bool $onlyBrickLess
     */
    public function __construct($onlyBrickLess = false) {
        $this->onlyBrickLess = $onlyBrickLess;
    }

    /**
     * @return boolean
     */
    public function isOnlyBrickLess() {
        return $this->onlyBrickLess;
    }
}