<?php namespace rtens\ucdi\es;

use rtens\ucdi\app\Application;

class ApplicationService {

    /** @var Application */
    private $application;

    /** @var EventStore */
    private $store;

    /**
     * @param Application $application
     * @param EventStore $store
     */
    public function __construct(Application $application, EventStore $store) {
        $this->application = $application;
        $this->store = $store;
        $this->applyEvents($store->load());
    }

    /**
     * @param object $command
     * @return object[] Resulting events
     */
    public function handle($command) {
        $events = $this->invokeMethod('handle', $command);
        $this->applyEvents($events);
        $this->store->save($events);
        return $events;
    }

    /**
     * @param object $query
     * @return mixed Result of the query
     */
    public function execute($query) {
        return $this->invokeMethod('execute', $query);
    }

    private function applyEvents($events) {
        foreach ($events as $event) {
            try {
                $this->invokeMethod('apply', $event);
            } catch (\ReflectionException $e) {
            }
        }
    }

    private function invokeMethod($prefix, $object) {
        $eventName = (new \ReflectionClass($object))->getShortName();

        $applyMethod = new \ReflectionMethod($this->application, $prefix . $eventName);
        return $applyMethod->invoke($this->application, $object);
    }
}