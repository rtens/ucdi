<?php namespace rtens\ucdi\es;

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