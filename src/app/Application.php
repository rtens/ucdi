<?php namespace rtens\ucdi\app;

class Application {

    private $eventStore;

    public function __construct(EventStore $eventStore) {
        $this->eventStore = $eventStore;
    }


    public function handle(Command $command) {
        $aggregate = $this->instantiateAggregate($command->aggregateClass());

        $events = $this->eventStore->load($command->aggregateId());
        foreach ($events as $event) {
            $this->invokeMethod($aggregate, 'apply', $event);
        }

        $newEvents = $this->invokeMethod($aggregate, 'handle', $command);

        $this->eventStore->save($newEvents);
        return $newEvents;
    }

    private function instantiateAggregate($class) {
        return new $class;
    }

    private function invokeMethod($aggregate, $prefix, $event) {
        $eventName = (new \ReflectionClass($event))->getShortName();
        $applyMethod = new \ReflectionMethod($aggregate, $prefix . $eventName);
        return $applyMethod->invoke($aggregate, $event);
    }
}