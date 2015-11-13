<?php namespace rtens\ucdi\app\commands;

use rtens\ucdi\app\model\Rating;

class RateGoal {

    /** @var string */
    private $goal;

    /** @var Rating */
    private $rating;

    /**
     * @param string $goal
     * @param Rating $rating
     */
    public function __construct($goal, Rating $rating) {
        $this->goal = $goal;
        $this->rating = $rating;
    }

    /**
     * @return string
     */
    public function getGoal() {
        return $this->goal;
    }

    /**
     * @return Rating
     */
    public function getRating() {
        return $this->rating;
    }
}