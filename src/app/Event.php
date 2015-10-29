<?php namespace rtens\ucdi\app;

interface Event {

    /**
     * @return AggregateId
     */
    public function aggregateId();
}