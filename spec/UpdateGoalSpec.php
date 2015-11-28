<?php
namespace spec\rtens\ucdi;

use rtens\domin\parameters\Html;
use rtens\scrut\fixtures\ExceptionFixture;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\UpdateGoal;
use rtens\ucdi\app\events\GoalNameChanged;
use rtens\ucdi\app\events\GoalNotesChanged;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property UpdateGoalSpec_DomainDriver driver <-
 */
class UpdateGoalSpec {

    function failIfGoalDoesNotExist() {
        $this->driver->whenITryToUpdate('foo');
        $this->driver->thenItShouldFailWith('The goal [Goal-foo] does not exist.');
    }

    function updateGoal() {
        $this->driver->givenAGoalWithName_WasCreated('foo');
        $this->driver->givenIProvideTheName('New name');
        $this->driver->givenIProvideTheNotes('New description');
        $this->driver->whenIUpdate('foo');
        $this->driver->thenTheNameOf_ShouldHaveChangedTo('foo', 'New name');
        $this->driver->thenTheNotesOf_ShouldHaveChangedTo('foo', 'New description');
    }

    function sameName() {
        $this->driver->givenAGoalWithName_WasCreated('Foo');
        $this->driver->givenIProvideTheName('Foo');
        $this->driver->whenIUpdate('Foo');
        $this->driver->thenTheNameOf_ShouldNotHaveChanged();
    }

    function sameNotes() {
        $this->driver->givenAGoalWithName_WasCreated('Foo');
        $this->driver->givenTheNotesOf_WereChangedTo('Foo', 'Some notes');
        $this->driver->givenIProvideTheNotes('Some notes');
        $this->driver->whenIUpdate('Foo');
        $this->driver->thenTheNotesOf_ShouldNotHaveChanged();
    }
}

/**
 * @property ExceptionFixture try <-
 */
class UpdateGoalSpec_DomainDriver extends DomainDriver {

    private $events;
    private $name;
    private $notes;

    public function givenAGoalWithName_WasCreated($goal) {
        $this->givenTheNextUidIs($goal);
        $this->service->handle(new CreateGoal($goal));
    }

    public function givenTheNotesOf_WereChangedTo($goal, $string) {
        $this->store->save([new GoalNotesChanged("Goal-$goal", $string)]);
        $this->givenNowIs('now');
    }

    public function givenIProvideTheName($string) {
        $this->name = $string;
    }

    public function givenIProvideTheNotes($string) {
        $this->notes = $string;
    }

    public function whenITryToUpdate($goal) {
        $this->try->tryTo(function () use ($goal) {
            $this->whenIUpdate($goal);
        });
    }

    public function whenIUpdate($goal) {
        $this->events = $this->service
            ->handle(new UpdateGoal("Goal-$goal", $this->name, new Html($this->notes)));
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function thenTheNameOf_ShouldHaveChangedTo($goal, $string) {
        $this->assert->contains($this->events, new GoalNameChanged("Goal-$goal", $string));
    }

    public function thenTheNotesOf_ShouldHaveChangedTo($goal, $string) {
        $this->assert->contains($this->events, new GoalNotesChanged("Goal-$goal", $string));
    }

    public function thenTheNameOf_ShouldNotHaveChanged() {
        $this->assert->size(array_filter($this->events, function ($event) {
            return $event instanceof GoalNameChanged;
        }), 0);
    }

    public function thenTheNotesOf_ShouldNotHaveChanged() {
        $this->assert->size(array_filter($this->events, function ($event) {
            return $event instanceof GoalNotesChanged;
        }), 0);
    }
}