<?php namespace rtens\ucdi\app\events;

class CalendarEventInserted {

    /** @var string */
    private $brickId;

    /** @var string */
    private $calendarEventId;

    /** @var string */
    private $calendarId = '';

    /**
     * @param string $brickId
     * @param string $calendarId
     * @param string $calendarEventId
     */
    public function __construct($brickId, $calendarId, $calendarEventId) {
        $this->brickId = $brickId;
        $this->calendarEventId = $calendarEventId;
        $this->calendarId = $calendarId;
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

    /**
     * @return string
     */
    public function getCalendarId() {
        return $this->calendarId;
    }

    function __toString() {
        return $this->brickId . ':' . $this->calendarEventId;
    }


}