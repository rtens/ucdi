<?php namespace rtens\ucdi;

use rtens\ucdi\app\Calendar;

class GoogleCalendar implements Calendar {

    /** @var \Google_Service_Calendar */
    private $service;

    public function __construct(\Google_Service_Calendar $cal) {
        $this->service = $cal;
    }

    /**
     * @param string $calendarId
     * @param string $summary
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param null|string $description
     * @return string Event ID in calendar
     */
    public function insertEvent($calendarId, $summary, \DateTimeImmutable $start, \DateTimeImmutable $end, $description = null) {
        $event = new \Google_Service_Calendar_Event();
        $event->setSummary($summary);
        $eventStart = new \Google_Service_Calendar_EventDateTime();
        $eventStart->setDateTime($start->format('c'));
        $event->setStart($eventStart);
        $eventEnd = new \Google_Service_Calendar_EventDateTime();
        $eventEnd->setDateTime($end->format('c'));
        $event->setEnd($eventEnd);
        $event->setDescription($description);
        $createdEvent = $this->service->events->insert($calendarId, $event);

        return $createdEvent->id;
    }

    /**
     * @param string $calendarId
     * @param string $eventId
     * @return null
     */
    public function deleteEvent($calendarId, $eventId) {
        $this->service->events->delete($calendarId, $eventId);
    }

    /**
     * @return string[]
     */
    public function availableCalendars() {
        $calendars = [];
        /** @var \Google_Service_Calendar_CalendarListEntry $calendar */
        foreach ($this->service->calendarList->listCalendarList() as $calendar) {
            $calendars[$calendar->id] = $calendar->summary;
        }
        return $calendars;
    }
}