<?php namespace rtens\ucdi\es;

use watoki\reflect\TypeFactory;
use watoki\stores\common\GenericSerializer;
use watoki\stores\common\Reflector;
use watoki\stores\Serializer;
use watoki\stores\SerializerRegistry;

class EventSerialilzer implements Serializer {

    /** @var SerializerRegistry */
    private $registry;

    /** @var TypeFactory */
    private $types;

    /**
     * @param SerializerRegistry $registry
     * @param TypeFactory $types
     */
    public function __construct(SerializerRegistry $registry, TypeFactory $types) {
        $this->registry = $registry;
        $this->types = $types;
    }

    /**
     * @param Event $inflated
     * @return array
     */
    public function serialize($inflated) {
        $class = get_class($inflated->getEvent());

        return [
            'occurred' => $inflated->getOccurred()->format('c'),
            'event' => [
                'type' => (string)new \watoki\reflect\type\ClassType($class),
                'value' => $this->getSerializer($class)->serialize($inflated->getEvent())
            ]
        ];
    }

    /**
     * @param array $serialized
     * @return Event
     */
    public function inflate($serialized) {
        $event = $serialized['event'];
        return new Event(
            $this->getSerializer($event['type'])->inflate($event['value']),
            new \DateTimeImmutable($serialized['occurred']));
    }

    private function getSerializer($class) {
        $reflector = new Reflector($class, $this->registry, $this->types);
        return $reflector->create(GenericSerializer::$CLASS);
    }
}