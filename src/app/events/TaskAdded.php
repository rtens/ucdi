<?php namespace rtens\ucdi\app\events;

class TaskAdded {

    /** @var string */
    private $taskId;

    /** @var string */
    private $goalId;

    /** @var string */
    private $description;

    /**
     * @param string $taskId
     * @param string $goalId
     * @param string $description
     */
    public function __construct($taskId, $goalId, $description) {
        $this->taskId = $taskId;
        $this->goalId = $goalId;
        $this->description = $description;
    }

}