<?php namespace rtens\ucdi\app;

interface EventStore {

    /**
     * @param AggregateId $id
     * @return EventStream
     */
    public function load(AggregateId $id);

    /**
     * @param Event[] $events
     * @return void
     */
    public function save($events);
}