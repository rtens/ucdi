<?php
namespace rtens\ucdi\es;

class MemoryEventStore implements EventStore {

    /**
     * @var object[]
     */
    private $events = [];

    /**
     * @return object[]
     */
    public function load() {
        return $this->events;
    }

    /**
     * @param object[] $events
     * @return void
     */
    public function save($events) {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }
}