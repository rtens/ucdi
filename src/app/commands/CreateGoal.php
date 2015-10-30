<?php namespace rtens\ucdi\app\commands;

class CreateGoal {

    /** @var string */
    private $name;

    /** @var null|string */
    private $notes;

    /**
     * @param string $name
     * @param null|string $notes
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
    public function getNotes() {
        return $this->notes;
    }

}