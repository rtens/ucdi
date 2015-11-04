<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\MarkTaskCompleted;
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
}