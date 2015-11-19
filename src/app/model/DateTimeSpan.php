<?php namespace rtens\ucdi\app\model;

class DateTimeSpan {

    /** @var \DateTimeImmutable */
    private $start;

    /** @var \DateTimeImmutable */
    private $end;

    /**
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     */
    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end) {
        $this->start = $start;
        $this->end = $end;
    }

    public function contains(\DateTimeImmutable $dateTime) {
        return $this->start < $dateTime && $this->end > $dateTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getStart() {
        return $this->start;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getEnd() {
        return $this->end;
    }
}