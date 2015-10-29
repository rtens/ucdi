<?php namespace rtens\ucdi\app;

class NoneAggregateId extends AggregateId {

    public function __construct() {
        parent::__construct('');
    }

}