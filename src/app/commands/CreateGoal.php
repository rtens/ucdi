<?php namespace rtens\ucdi\app\commands;

use rtens\ucdi\app\Rating;

class CreateGoal {

    /** @var string */
    private $name;

    /** @var null|\rtens\domin\parameters\Html */
    private $notes;

    /** @var null|Rating  */
    private $rating;

    /** @var array|string[] */
    private $tasks = [];

    /**
     * @param string $name
     * @param null|\rtens\domin\parameters\Html $notes
     */
    public function __construct($name, $notes = null) {
        $this->name = $name;
        $this->notes = $notes;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return null|string
     */
    public function getNotesContent() {
        return $this->notes ? $this->notes->getContent() : null;
    }

    /**
     * @return null|Rating
     */
    public function getRating() {
        return $this->rating;
    }

    /**
     * @param null|Rating $rating
     */
    public function setRating($rating) {
        $this->rating = $rating;
    }

    /**
     * @param string $description
     */
    public function addTask($description) {
        $this->tasks[] = $description;
    }

    /**
     * @param null|array|string[] $tasks
     */
    public function setTasks($tasks) {
        $this->tasks = $tasks ?: [];
    }

    /**
     * @return array|string[]
     */
    public function getAllTasks() {
        return $this->tasks;
    }

}