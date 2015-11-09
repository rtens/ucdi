<?php namespace rtens\ucdi\app;

class Rating {

    const MIN = 0;
    const MAX = 10;

    private static $quadrantName = [
        0 => 'unknown',
        1 => 'now',
        2 => 'schedule',
        3 => 'delegate',
        4 => 'delete'
    ];

    /** @var int In range [0,10] */
    private $importance;

    /** @var int Int range [0,10] */
    private $urgency;

    /**
     * @param int $urgency
     * @param int $importance
     * @throws \Exception
     */
    public function __construct($urgency, $importance) {
        if ($importance < self::MIN || $importance > self::MAX) {
            throw new \Exception('Importance must be between 0 and 10');
        }
        if ($urgency < self::MIN || $urgency > self::MAX) {
            throw new \Exception('Urgency must be between 0 and 10');
        }
        $this->importance = $importance;
        $this->urgency = $urgency;
    }

    /**
     * @return int
     */
    public function getImportance() {
        return $this->importance;
    }

    /**
     * @return int
     */
    public function getUrgency() {
        return $this->urgency;
    }

    /**
     * @return string
     */
    public function getAction() {
        return self::$quadrantName[$this->getQuadrant()];
    }

    /**
     * @return int
     */
    public function getQuadrant() {
        $limit = self::MAX / 2;

        if ($this->importance >= $limit && $this->urgency >= $limit) {
            return 1;
        } else if ($this->importance >= $limit && $this->urgency < $limit) {
            return 2;
        } else if ($this->importance < $limit && $this->urgency >= $limit) {
            return 3;
        } else if ($this->importance < $limit && $this->urgency < $limit) {
            return 4;
        } else {
            return 0;
        }
    }

    function __toString() {
        return $this->getAction() . ' (U' . $this->getUrgency() . ',I' . $this->getImportance() . ')';
    }
}