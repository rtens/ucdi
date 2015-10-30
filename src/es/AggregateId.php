<?php namespace rtens\ucdi\es;

abstract class AggregateId {

    private $id;

    /**
     * @param string $id
     */
    public function __construct($id) {
        $this->id = $id;
    }

    public static function generate() {
        return new static(uniqid((new \ReflectionClass(static::class))->getShortName(). '-'));
    }

    public function __toString() {
        return $this->id;
    }
}