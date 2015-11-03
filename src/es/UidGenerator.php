<?php namespace rtens\ucdi\es;

class UidGenerator {

    public function generate($prefix = null) {
        return uniqid($prefix ? $prefix : '');
    }
}