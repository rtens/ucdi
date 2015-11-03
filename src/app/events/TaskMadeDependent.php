<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;

class TaskMadeDependent extends Event {

    /** @var string */
    private $taskId;

    /** @var string */
    private $dependency;

    /**
     * @param string $taskId
     * @param string $dependency
     */
    public function __construct($taskId, $dependency) {
        parent::__construct();
        $this->taskId = $taskId;
        $this->dependency = $dependency;
    }

    /**
     * @return string
     */
    public function getTaskId() {
        return $this->taskId;
    }

    /**
     * @return string
     */
    public function getDependency() {
        return $this->dependency;
    }
}