<?php
namespace rtens\ucdi\app\commands;

use rtens\domin\parameters\Html;

class UpdateGoal {

    /** @var string */
    private $goal;

    /** @var string */
    private $name;

    /** @var Html */
    private $notes;

    /**
     * @param string $goal
     * @param string $name
     * @param Html $notes
     */
    public function __construct($goal, $name, $notes) {
        $this->goal = $goal;
        $this->name = $name;
        $this->notes = $notes;
    }

    /**
     * @return string
     */
    public function getGoal() {
        return $this->goal;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return Html
     */
    public function getNotes() {
        return $this->notes;
    }
}