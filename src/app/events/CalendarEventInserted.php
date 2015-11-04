<?php namespace rtens\ucdi\app\events;

use rtens\ucdi\es\Event;

class CalendarEventInserted extends Event {

    /** @var string */
    private $brickId;

    /** @var string */
    private $calendarEventId;

    /**
     * @param string $brickId
     * @param string $calendarEventId
     */
    public function __construct($brickId, $calendarEventId) {
        parent::__construct();
        $this->brickId = $brickId;
        $this->calendarEventId = $calendarEventId;
    }

    /**
     * @return string
     */
    public function getBrickId() {
        return $this->brickId;
    }

    /**
     * @return string
     */
    public function getCalendarEventId() {
        return $this->calendarEventId;
    }

    function __toString() {
        return $this->brickId . $this->calendarEventId;
    }


}