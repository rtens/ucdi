<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\Application;
use rtens\ucdi\es\CommandHandler;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\es\UidGenerator;

/**
 * @property CreateGoalSpec_DomainDriver driver <-
 */
class CreateGoalSpec {

    function minimalGoal() {
        $this->driver->whenICreateTheGoal('Test');
        $this->driver->thenAGoal_ShouldBeCreated('Test');
    }

    function goalWithNotes() {
        $this->driver->whenICreateAGoalWithNotes('Foo bar');
        $this->driver->thenTheNotesOfTheEventShouldBeSetTo('Foo bar');
    }

}

interface CreateGoalSpec_Driver {

    public function whenICreateTheGoal($name);

    public function thenAGoal_ShouldBeCreated($name);

    public function whenICreateAGoalWithNotes($notes);

    public function thenTheNotesOfTheEventShouldBeSetTo($notes);
}

/**
 * @property \rtens\scrut\Assert assert <-
 */
class CreateGoalSpec_DomainDriver implements CreateGoalSpec_Driver {

    /** @var CommandHandler */
    private $app;

    /** @var object[] */
    private $events;

    public function __construct() {
        $this->app = new CommandHandler(new Application(new UidGenerator()));
    }

    public function whenICreateTheGoal($name) {
        $this->events = $this->app->handle(new CreateGoal($name));
    }

    public function thenAGoal_ShouldBeCreated($name) {
        $this->assert->size($this->events, 1);

        /** @var GoalCreated $event */
        $event = $this->events[0];

        $this->assert->isInstanceOf($event, GoalCreated::class);
        $this->assert->equals($event->getName(), $name);
    }

    public function whenICreateAGoalWithNotes($notes) {
        $this->events = $this->app->handle(new CreateGoal('Foo', $notes));
    }

    public function thenTheNotesOfTheEventShouldBeSetTo($notes) {
        $this->assert->size($this->events, 2);

        /** @var GoalNotesChanged $event */
        $event = $this->events[1];

        $this->assert->isInstanceOf($event, GoalNotesChanged::class);
        $this->assert->equals($event->getNotes(), $notes);
    }
}