<?php namespace spec\rtens\ucdi;

use rtens\mockster\Mockster;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\events\BrickCancelled;
use rtens\ucdi\app\events\TaskMarkedCompleted;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property MarkTaskAsCompletedSpec_DomainDriver driver <-
 */
class MarkTaskAsCompletedSpec {

    function before() {
        $this->driver->givenNowIs('2011-12-13 14:15:16');
    }

    function taskMustExist() {
        $this->driver->whenITryToMarkTheTaskAsCompleted();
        $this->driver->thenItShouldFailWith('Task [Task-2] does not exist.');
    }

    function success() {
        $this->driver->givenATask();
        $this->driver->whenIMarkTheTaskAsCompleted();
        $this->driver->thenTheTaskShouldBeMarkedAsCompleted('2011-12-13 14:15:16');
    }

    function cannotMarkCompletedTaskAsCompleted() {
        $this->driver->givenATask();
        $this->driver->whenIMarkTheTaskAsCompleted();
        $this->driver->whenITryToMarkTheTaskAsCompleted();
        $this->driver->thenItShouldFailWith('Task [Task-2] was already completed [2011-12-13 14:15].');
    }

    function removeUpcomingBricks() {
        $this->driver->givenATask();
        $this->driver->givenABrick_ScheduledIn('Foo', '1 hour');
        $this->driver->givenABrick_ScheduledIn('Bar', '3 hours');
        $this->driver->givenABrick_ScheduledIn('Baz', '4 hours');
        $this->driver->givenBrick_IsMarkedAsLaid('Baz');

        $this->driver->givenNowIs('2 hours');
        $this->driver->whenIMarkTheTaskAsCompleted();
        $this->driver->thenBrick_ShouldNotBeCancelled('Foo');
        $this->driver->thenBrick_ShouldNotBeCancelled('Baz');
        $this->driver->thenBrick_ShouldBeCancelled('Bar');
    }
}

/**
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class MarkTaskAsCompletedSpec_DomainDriver extends DomainDriver {

    private $events;

    public function givenATask() {
        $this->service->handle(new \rtens\ucdi\app\commands\CreateGoal('Foo'));
        $this->service->handle(new \rtens\ucdi\app\commands\AddTask('Goal-1', 'Task Foo'));
    }

    public function givenABrick_ScheduledIn($description, $when) {
        $this->givenTheNextUidIs($description);
        $this->service->handle(new \rtens\ucdi\app\commands\ScheduleBrick('Task-2', $description, new \DateTimeImmutable($when), new \DateInterval('PT1H')));
    }

    public function givenBrick_IsMarkedAsLaid($description) {
        $this->service->handle(new \rtens\ucdi\app\commands\MarkBrickLaid("Brick-$description"));
    }

    public function whenIMarkTheTaskAsCompleted() {
        $this->events = $this->service->handle(new MarkTaskCompleted('Task-2'));
    }

    public function whenITryToMarkTheTaskAsCompleted() {
        $this->try->tryTo(function () {
            $this->whenIMarkTheTaskAsCompleted();
        });
    }

    public function thenTheTaskShouldBeMarkedAsCompleted($when) {
        $this->assert->contains($this->events, new TaskMarkedCompleted('Task-2', new \DateTimeImmutable($when)));
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function thenBrick_ShouldBeCancelled($description) {
        $this->assert->contains($this->events, new BrickCancelled("Brick-$description"));

        Mockster::stub($this->calendar->deleteEvent('myCalendarId', "Event-$description"))
            ->shouldHave()->beenCalled();
    }

    public function thenBrick_ShouldNotBeCancelled($description) {
        $this->assert->not()->contains($this->events, new BrickCancelled("Brick-$description"));
    }
}