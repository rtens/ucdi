<?php namespace rtens\ucdi\es;

class EventStore {

    private $events = [];

    public function save($events) {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    public function load() {
        return $this->events;
    }
}