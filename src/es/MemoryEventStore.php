<?php
namespace rtens\ucdi\es;

class MemoryEventStore implements EventStore {

    /**
     * @var EventStream[]
     */
    private $events = [];

    /**
     * @param \rtens\ucdi\es\AggregateId $id
     * @return EventStream
     */
    public function load(AggregateId $id) {
        if (array_key_exists((string)$id, $this->events)) {
            return $this->events[(string)$id];
        }
        return new EventStream();
    }

    /**
     * @param \rtens\ucdi\es\Event[] $events
     * @return void
     */
    public function save($events) {
        foreach ($events as $event) {
            $id = $event->aggregateId();

            if (!array_key_exists((string)$id, $this->events)) {
                $this->events[(string)$id] = new EventStream();
            }

            $this->events[(string)$id]->add($event);
        }
    }
}