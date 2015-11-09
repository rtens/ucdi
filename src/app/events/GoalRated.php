<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\app\Rating;

class GoalRated {

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