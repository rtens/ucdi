<?php namespace rtens\ucdi\es;

use rtens\domin\reflection\types\TypeFactory;
use watoki\stores\common\GenericSerializer;
use watoki\stores\common\Reflector;
use watoki\stores\file\FileStore;
use watoki\stores\SerializerRegistry;

class PersistentEventStore extends EventStore {

    private $file;

    private $registry;

    private $types;

    public function __construct($file) {
        $this->types = new TypeFactory();
        $this->registry = new SerializerRegistry();
        FileStore::registerDefaultSerializers($this->registry);

        $this->file = $file;

        if (!file_exists(dirname($this->file))) {
            mkdir(dirname($this->file), 0777, true);
        }
    }

    public function save($events) {
        $serialized = [];
        foreach (array_merge($this->load(), $events) as $event) {
            $serialized[] = [
                'class' => get_class($event),
                'data' => $this->getSerializer(get_class($event))->serialize($event)
            ];
        }

        file_put_contents($this->file, json_encode($serialized, JSON_PRETTY_PRINT));
    }

    public function load() {
        if (!file_exists($this->file)) {
            return [];
        }

        $events = [];
        foreach (json_decode(file_get_contents($this->file), true) as $serialized) {
            $events[] = $this->getSerializer($serialized['class'])->inflate($serialized['data']);
        }

        return $events;
    }

    private function getSerializer($class) {
        $reflector = new Reflector($class, $this->registry, $this->types);
        return $reflector->create(GenericSerializer::$CLASS);
    }

}