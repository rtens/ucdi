<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\Application;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\es\CommandHandler;
use spec\rtens\ucdi\fakes\FakeUidGenerator;

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
        $this->app = new CommandHandler(new Application(new FakeUidGenerator()));
    }

    public function whenICreateTheGoal($name) {
        $this->events = $this->app->handle(new CreateGoal($name));
    }

    public function thenAGoal_ShouldBeCreated($name) {
        $this->assert->contains($this->events, new GoalCreated('Goal-1', $name));
    }

    public function whenICreateAGoalWithNotes($notes) {
        $this->events = $this->app->handle(new CreateGoal('Foo', $notes));
    }

    public function thenTheNotesOfTheEventShouldBeSetTo($notes) {
        $this->assert->contains($this->events, new GoalNotesChanged('Goal-1', $notes));
    }
}