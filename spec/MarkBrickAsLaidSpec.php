<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickMarkedLaid;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property MarkBrickAsLaidSpec_DomainDriver driver <-
 */
class MarkBrickAsLaidSpec {

    function before() {
        $this->driver->givenNowIs('2011-12-13 14:15:16');
        $this->driver->givenTheBrick('Foo');
    }

    function success() {
        $this->driver->whenIMark_AsLaid('Brick-1');
        $this->driver->then_ShouldBeMarkedAsLaid('Brick-1', '2011-12-13 14:15:16');
    }

    function cannotMarkLaidBrickAsLaid() {
        $this->driver->whenIMark_AsLaid('Brick-1');
        $this->driver->whenITryToMark_AsLaid('Brick-1');
        $this->driver->thenItShouldFailWith('Brick [Brick-1] was already laid [2011-12-13 14:15].');
    }
}

/**
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class MarkBrickAsLaidSpec_DomainDriver extends DomainDriver {

    private $events;

    public function givenTheBrick($description) {
        $this->service->handle(new CreateGoal('GoalFoo'));
        $this->service->handle(new AddTask('Goal-1', 'TaskFoo'));
        $this->service->handle(new ScheduleBrick('Task-2', $description, new \DateTimeImmutable('tomorrow'), new \DateInterval('PT1H')));
    }

    public function whenIMark_AsLaid($brickId) {
        $this->events = $this->service->handle(new MarkBrickLaid($brickId));
    }

    public function then_ShouldBeMarkedAsLaid($brickId, $when) {
        $this->assert->contains($this->events, new BrickMarkedLaid($brickId, new \DateTimeImmutable($when)));
    }

    public function whenITryToMark_AsLaid($brickId) {
        $this->try->tryTo(function () use ($brickId) {
            $this->whenIMark_AsLaid($brickId);
        });
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }
}