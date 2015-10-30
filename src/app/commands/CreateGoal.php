<?php namespace rtens\ucdi\app\commands;

class CreateGoal {

    /** @var string */
    private $name;

    /** @var string */
    private $notes;

    /**
     * @param string $name
     * @param string $notes
     */
    public function __construct($name, $notes = '') {
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
     * @return string
     */
    public function getNotes() {
        return $this->notes;
    }

}