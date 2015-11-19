<?php namespace rtens\ucdi\app\queries;

use rtens\ucdi\app\model\DateTimeSpan;

class ReportEfforts {

    /** @var null|string */
    private $goal;

    /** @var null|DateTimeSpan */
    private $timeSpan;

    /**
     * @param null|string $goal
     * @return ReportEfforts
     */
    public function setGoal($goal) {
        $this->goal = $goal;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getGoal() {
        return $this->goal;
    }

    /**
     * @param null|DateTimeSpan $timeSpan
     * @return ReportEfforts
     */
    public function setTimeSpan($timeSpan) {
        $this->timeSpan = $timeSpan;
        return $this;
    }

    /**
     * @return null|DateTimeSpan
     */
    public function getTimeSpan() {
        return $this->timeSpan;
    }
}