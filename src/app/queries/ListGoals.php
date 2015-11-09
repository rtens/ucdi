<?php namespace rtens\ucdi\app\queries;

class ListGoals {

    /** @var bool */
    private $onlyBrickLess;

    /** @var bool */
    private $achieved;

    /**
     * @param bool $onlyBrickLess
     * @param bool $achieved
     */
    public function __construct($onlyBrickLess = false, $achieved = false) {
        $this->onlyBrickLess = $onlyBrickLess;
        $this->achieved = $achieved;
    }

    /**
     * @return boolean
     */
    public function isOnlyBrickLess() {
        return $this->onlyBrickLess;
    }

    /**
     * @return boolean
     */
    public function isAchieved() {
        return $this->achieved;
    }
}