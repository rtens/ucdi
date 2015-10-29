<?php namespace rtens\ucdi\app;

class EventStream {

    /** @var Event[] */
    private $events = [];

    public function add(Event $event) {
        $this->events[] = $event;
    }
}