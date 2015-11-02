<?php namespace rtens\ucdi\es;

use rtens\ucdi\app\Application;

class ApplicationService {

    /** @var Application */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application) {
        $this->application = $application;
    }

    /**
     * @param object $command
     * @return object[] Resulting events
     */
    public function handle($command) {
        return $this->invokeMethod('handle', $command);
    }

    private function invokeMethod($prefix, $event) {
        $eventName = (new \ReflectionClass($event))->getShortName();
        $applyMethod = new \ReflectionMethod($this->application, $prefix . $eventName);
        return $applyMethod->invoke($this->application, $event);
    }
}