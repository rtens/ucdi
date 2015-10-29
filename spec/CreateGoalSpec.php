<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\Application;
use rtens\ucdi\commands\CreateGoal;
use rtens\ucdi\events\GoalCreated;
use rtens\ucdi\store\MemoryEventStore;

/**
 * @property CreateGoalSpec_DomainDriver driver <-
 */
class CreateGoalSpec {

    function minimalGoal() {
        $this->driver->whenICreateTheGoal('Test');
        $this->driver->thenAGoal_ShouldHaveBeenCreated('Test');
    }

}

interface CreateGoalSpec_Driver {

    public function whenICreateTheGoal($name);

    public function thenAGoal_ShouldHaveBeenCreated($name);
}

/**
 * @property \rtens\scrut\Assert assert <-
 */
class CreateGoalSpec_DomainDriver implements CreateGoalSpec_Driver {

    /** @var \rtens\ucdi\app\Event[] */
    private $events;

    public function whenICreateTheGoal($name) {
        $app = new Application(new MemoryEventStore());
        $this->events = $app->handle(new CreateGoal($name));
    }

    public function thenAGoal_ShouldHaveBeenCreated($name) {
        /** @var GoalCreated $event */
        $event = $this->events[0];

        $this->assert->isInstanceOf($event, GoalCreated::class);
        $this->assert->equals($event->getName(), $name);
    }
}