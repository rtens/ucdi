<?php namespace rtens\ucdi\es;

class PersistentEventStore extends EventStore {

    private $file;

    public function __construct($file) {
        $this->file = $file;
        if (!file_exists(dirname($this->file))) {
            mkdir(dirname($this->file), 0777, true);
        }
    }

    public function save($events) {
        file_put_contents($this->file, json_encode(array_map('serialize', array_merge($this->load(), $events)), JSON_PRETTY_PRINT));
    }

    public function load() {
        return array_map('unserialize', json_decode(file_get_contents($this->file), true)) ?: [];
    }

}