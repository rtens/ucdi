<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\events\GoalMarkedAchieved;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property MarkGoalAsAchievedSpec_DomainDriver driver <-
 */
class MarkGoalAsAchievedSpec {

    function before() {
        $this->driver->givenNowIs('2011-12-13 14:15:16');
    }

    function goalMustExist() {
        $this->driver->whenITryToMarkTheGoalAsAchieved();
        $this->driver->thenItShouldFailWith('Goal [Goal-1] does not exist.');
    }

    function success() {
        $this->driver->givenAGoal();
        $this->driver->whenIMarkTheGoalAsAchieved();
        $this->driver->thenTheGoalShouldBeMarkedAsAchieved('2011-12-13 14:15:16');
    }

    function cannotMarkAchievedGoalAsAchieved() {
        $this->driver->givenAGoal();
        $this->driver->whenIMarkTheGoalAsAchieved();
        $this->driver->whenITryToMarkTheGoalAsAchieved();
        $this->driver->thenItShouldFailWith('Task [Goal-1] was already completed [2011-12-13 14:15].');
    }
}

/**
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class MarkGoalAsAchievedSpec_DomainDriver extends DomainDriver {

    private $events;

    public function givenAGoal() {
        $this->service->handle(new CreateGoal('Foo'));
    }

    public function whenIMarkTheGoalAsAchieved() {
        $this->events = $this->service->handle(new MarkGoalAchieved('Goal-1'));
    }

    public function thenTheGoalShouldBeMarkedAsAchieved($when) {
        $this->assert->contains($this->events, new GoalMarkedAchieved('Goal-1', new \DateTimeImmutable($when)));
    }

    public function whenITryToMarkTheGoalAsAchieved() {
        $this->try->tryTo(function () {
            $this->whenIMarkTheGoalAsAchieved();
        });
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }
}