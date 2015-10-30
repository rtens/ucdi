<?php namespace rtens\ucdi\app\commands;

use rtens\ucdi\es\Command;
use rtens\ucdi\es\NoneAggregateId;
use rtens\ucdi\app\Goal;

class CreateGoal implements Command {

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
     * @return \rtens\ucdi\es\AggregateId
     */
    public function aggregateId() {
        return new NoneAggregateId();
    }

    /**
     * @return string
     */
    public function aggregateClass() {
        return Goal::class;
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