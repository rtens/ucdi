<?php namespace rtens\ucdi\es;

class EventStream {

    /** @var Event[] */
    private $events = [];

    public function add(Event $event) {
        $this->events[] = $event;
    }
}