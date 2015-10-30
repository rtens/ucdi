<?php namespace rtens\ucdi\es;

interface Event {

    /**
     * @return AggregateId
     */
    public function aggregateId();
}