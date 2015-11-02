<?php namespace rtens\ucdi\app;

class Time {

    public function now() {
        return new \DateTimeImmutable();
    }
}