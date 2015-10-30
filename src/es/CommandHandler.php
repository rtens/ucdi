<?php namespace rtens\ucdi\es;

use rtens\ucdi\app\Application;

class CommandHandler {

    /** @var EventStore */
    private $eventStore;

    /** @var Application */
    private $application;

    /**
     * @param EventStore $eventStore
     * @param Application $application
     */
    public function __construct(EventStore $eventStore, Application $application) {
        $this->eventStore = $eventStore;
        $this->application = $application;
    }

    /**
     * @param object $command
     * @return object[] Resulting events
     */
    public function handle($command) {
        $events = $this->eventStore->load();
        foreach ($events as $event) {
            $this->invokeMethod('apply', $event);
        }

        $newEvents = $this->invokeMethod('handle', $command);

        $this->eventStore->save($newEvents);
        return $newEvents;
    }

    private function invokeMethod($prefix, $event) {
        $eventName = (new \ReflectionClass($event))->getShortName();
        $applyMethod = new \ReflectionMethod($this->application, $prefix . $eventName);
        return $applyMethod->invoke($this->application, $event);
    }
}