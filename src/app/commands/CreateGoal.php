<?php namespace rtens\ucdi\app\commands;

use rtens\ucdi\app\Rating;

class CreateGoal {

    /** @var string */
    private $name;

    /** @var null|\rtens\domin\parameters\Html */
    private $notes;

    /** @var null|Rating  */
    private $rating;

    /**
     * @param string $name
     * @param null|\rtens\domin\parameters\Html $notes
     * @param Rating $rating
     */
    public function __construct($name, $notes = null, Rating $rating = null) {
        $this->name = $name;
        $this->notes = $notes;
        $this->rating = $rating;
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

}