<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use spec\rtens\ucdi\drivers\DomainDriver;

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

/**
 * @property \rtens\scrut\Assert assert <-
 */
class CreateGoalSpec_DomainDriver extends DomainDriver {

    /** @var object[] */
    private $events;

    public function whenICreateTheGoal($name) {
        $this->events = $this->service->handle(new CreateGoal($name));
    }

    public function thenAGoal_ShouldBeCreated($name) {
        $this->assert->contains($this->events, new GoalCreated('Goal-1', $name));
    }

    public function whenICreateAGoalWithNotes($notes) {
        $this->events = $this->service->handle(new CreateGoal('Foo', $notes));
    }

    public function thenTheNotesOfTheEventShouldBeSetTo($notes) {
        $this->assert->contains($this->events, new GoalNotesChanged('Goal-1', $notes));
    }
}