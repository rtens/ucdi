<?php namespace rtens\ucdi\app\commands;

class CreateGoal {

    /** @var string */
    private $name;

    /** @var null|\rtens\domin\parameters\Html */
    private $notes;

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

}