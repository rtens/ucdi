<?php namespace spec\rtens\ucdi;

use rtens\domin\parameters\Html;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\GoalRated;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\Rating;
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

    function withRating() {
        $this->driver->whenICreateAGoalWithTheRating(7, 9);
        $this->driver->thenTheGoal_ShouldBeRatedWith_And('Goal-1', 7, 9);
    }

    function withTasks() {
        $this->driver->whenICreateAGoalWith_Tasks(3);
        $this->driver->then_TasksShouldHaveBeenAddedTo(3, 'Goal-1');
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

    public function whenICreateAGoalWithTheRating($urgency, $importance) {
        $command = new CreateGoal('Foo');
        $command->setRating(new Rating($urgency, $importance));
        $this->events = $this->service->handle($command);
    }

    public function whenICreateAGoalWithNotes($notes) {
        $this->events = $this->service->handle(new CreateGoal('Foo', new Html($notes)));
    }

    public function whenICreateAGoalWith_Tasks($count) {
        $command = new CreateGoal('Foo');
        foreach (range(1, $count) as $i) {
            $command->addTask("Task $i");
        }
        $this->events = $this->service->handle($command);
    }

    public function thenTheNotesOfTheEventShouldBeSetTo($notes) {
        $this->assert->contains($this->events, new GoalNotesChanged('Goal-1', $notes));
    }

    public function thenTheGoal_ShouldBeRatedWith_And($goalId, $urgency, $importance) {
        $this->assert->contains($this->events, new GoalRated($goalId, new Rating($urgency, $importance)));
    }

    public function then_TasksShouldHaveBeenAddedTo($count, $goalId) {
        $this->assert->size(array_filter($this->events, function ($event) use ($goalId) {
            return $event instanceof TaskAdded && $event->getGoalId() == $goalId;
        }), $count);
    }
}