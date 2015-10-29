<?php namespace rtens\ucdi\app;

interface Command {

    /**
     * @return AggregateId
     */
    public function aggregateId();

    /**
     * @return string
     */
    public function aggregateClass();
}