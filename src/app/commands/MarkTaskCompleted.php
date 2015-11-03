<?php namespace rtens\ucdi\app\commands;

class MarkTaskCompleted {

    /** @var string */
    private $task;

    /**
     * @param string $task
     */
    public function __construct($task) {
        $this->task = $task;
    }

    /**
     * @return string
     */
    public function getTask() {
        return $this->task;
    }
}