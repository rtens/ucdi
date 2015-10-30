<?php namespace rtens\ucdi\es;

class NoneAggregateId extends AggregateId {

    public function __construct() {
        parent::__construct('');
    }

}