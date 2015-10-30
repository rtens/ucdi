<?php namespace rtens\ucdi\es;

interface EventStore {

    /**
     * @return object[]
     */
    public function load();

    /**
     * @param object[] $events
     * @return void
     */
    public function save($events);
}