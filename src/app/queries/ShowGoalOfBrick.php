<?php
namespace rtens\ucdi\app\queries;

class ShowGoalOfBrick {

    private $brick;

    /**
     * @param string $brick
     */
    public function __construct($brick) {
        $this->brick = $brick;
    }

    /**
     * @return mixed
     */
    public function getBrick() {
        return $this->brick;
    }
}