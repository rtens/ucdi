<?php namespace rtens\ucdi;

use rtens\ucdi\app\Calendar;

class GoogleCalendar implements Calendar {

    /** @var \Google_Service_Calendar */
    private $service;

    private static $calendarId = '46p4opal5ifts2p9tb18mhm170@group.calendar.google.com';

    public function __construct(\Google_Service_Calendar $cal) {
        $this->service = $cal;
    }

    /**
     * @param string $summary
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param null|string $description
     * @return string Event ID in calendar
     */
    public function insertEvent($summary, \DateTimeImmutable $start, \DateTimeImmutable $end, $description = null) {
        $event = new \Google_Service_Calendar_Event();
        $event->setSummary($summary);
        $eventStart = new \Google_Service_Calendar_EventDateTime();
        $eventStart->setDateTime($start->format('c'));
        $event->setStart($eventStart);
        $eventEnd = new \Google_Service_Calendar_EventDateTime();
        $eventEnd->setDateTime($end->format('c'));
        $event->setEnd($eventEnd);
        $createdEvent = $this->service->events->insert(self::$calendarId, $event);

        return $createdEvent->id;
    }
}