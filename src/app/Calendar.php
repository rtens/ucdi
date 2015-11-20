<?php
namespace rtens\ucdi\app;

interface Calendar {

    /**
     * @param $calendarId
     * @param string $summary
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param null|string $description
     * @return string Event ID
     */
    public function insertEvent($calendarId, $summary, \DateTimeImmutable $start, \DateTimeImmutable $end, $description = null);

    /**
     * @param $calendarId
     * @param string $eventId
     * @return null
     */
    public function deleteEvent($calendarId, $eventId);

    /**
     * @return string[]
     */
    public function availableCalendars();
}