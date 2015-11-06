<?php namespace rtens\ucdi\es;

use rtens\domin\reflection\types\TypeFactory;
use watoki\stores\common\factories\ClassSerializerFactory;
use watoki\stores\common\Reflector;
use watoki\stores\file\FileStore;
use watoki\stores\file\serializers\JsonSerializer;
use watoki\stores\SerializerRegistry;

class PersistentEventStore extends EventStore {

    public function __construct($file, \DateTimeImmutable $now = null) {
        $this->now = $now ?: new \DateTimeImmutable();
        $this->key = basename($file);

        $types = new TypeFactory();
        $registry = new SerializerRegistry();
        $registry->add(new ClassSerializerFactory(Event::class, new EventSerialilzer($registry, $types)));

        FileStore::registerDefaultSerializers($registry);
        $reflector = new Reflector(EventStream::class, $registry, $types);
        $serializer = $reflector->create(JsonSerializer::$CLASS);
        $this->store = new FileStore($serializer, dirname($file));
    }

    public function save($events) {
        $stream = $this->read();
        foreach ($events as $event) {
            $stream->add(new Event($event, $this->now));
        }
        $this->store->create($stream, $this->key);
    }

    public function load() {
        $events = [];
        foreach ($this->read()->getEvents() as $event) {
            $events[] = $event->getEvent();
        }
        return $events;
    }

    private function read() {
        if (!$this->store->hasKey($this->key)) {
            return new EventStream();
        }
        return $this->store->read($this->key);
    }

}