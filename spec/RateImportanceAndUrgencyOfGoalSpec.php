<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\RateGoal;
use rtens\ucdi\app\events\GoalRated;
use rtens\ucdi\app\Rating;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property RateImportanceAndUrgencyOfGoalSpec_DomainDriver driver <-
 */
class RateImportanceAndUrgencyOfGoalSpec {

    function before() {
        $this->driver->givenAGoal();
    }

    function nonExistingGoal() {
        $this->driver->whenITryToRate_WithUrgency_AndImportance('Goal-Foo', 0, 0);
        $this->driver->thenItShouldFailWith('Goal [Goal-Foo] does not exist.');
    }

    function invalidRating() {
        $this->driver->whenITryToRate_WithUrgency_AndImportance('Goal-1', 0, -1);
        $this->driver->thenItShouldFailWith('Importance must be between 0 and 10');

        $this->driver->whenITryToRate_WithUrgency_AndImportance('Goal-1', 0, 11);
        $this->driver->thenItShouldFailWith('Importance must be between 0 and 10');

        $this->driver->whenITryToRate_WithUrgency_AndImportance('Goal-1', -1, 0);
        $this->driver->thenItShouldFailWith('Urgency must be between 0 and 10');

        $this->driver->whenITryToRate_WithUrgency_AndImportance('Goal-1', 11, 0);
        $this->driver->thenItShouldFailWith('Urgency must be between 0 and 10');
    }

    function successfulRating() {
        $this->driver->whenIRate_WithUrgency_AndImportance('Goal-1', 9, 1);
        $this->driver->then_ShouldBeRatedWithUrgency_AndImportance('Goal-1', 9, 1);
    }
}

/**
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class RateImportanceAndUrgencyOfGoalSpec_DomainDriver extends DomainDriver {

    protected $events;

    public function givenAGoal() {
        $this->service->handle(new CreateGoal('Foo'));
    }

    public function whenIRate_WithUrgency_AndImportance($goalId, $urgency, $importance) {
        $this->events = $this->service->handle(new RateGoal($goalId, new Rating($urgency, $importance)));
    }

    public function whenITryToRate_WithUrgency_AndImportance($goalId, $urgency, $importance) {
        $this->try->tryTo(function () use ($goalId, $importance, $urgency) {
            $this->whenIRate_WithUrgency_AndImportance($goalId, $urgency, $importance);
        });
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function then_ShouldBeRatedWithUrgency_AndImportance($goalId, $urgency, $importance) {
        $this->assert->contains($this->events, new GoalRated($goalId, new Rating($urgency, $importance)));
    }
}