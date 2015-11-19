<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\LogEffort;
use rtens\ucdi\app\events\EffortLogged;

/**
 * @property LogEffortSpec_DomainDriver driver <-
 */
class LogEffortSpec {

    function before() {
        $this->driver->givenTheTask('Foo');
    }

    function taskMustExist() {
        $this->driver->whenITryToLogEffortFor('Bar');
        $this->driver->thenItShouldFailWith('Task [Task-Bar] does not exist.');
    }

    function endMustNotBeBeforeStart() {
        $this->driver->whenITryToLogEffortFor_From_Until('Foo', 'now', '1 second ago');
        $this->driver->thenItShouldFailWith("The end time must be after the start time");
    }

    function endMustNotBeSameAsStart() {
        $this->driver->whenITryToLogEffortFor_From_Until('Foo', 'now', 'now');
        $this->driver->thenItShouldFailWith("The end time must be after the start time");
    }

    function success() {
        $this->driver->whenILogEffortFor_From_Until('Foo', '14:00', '15:00');
        $this->driver->thenAnEffortShouldBeLoggedFor_Starting_Ending('Foo', '14:00:00', '15:00:00');
    }

    function withComment() {
        $this->driver->whenILogEffortFor_WithComment('Foo', 'Some comment');
        $this->driver->thenAnEffortShouldBeLoggedFor_WithComment('Foo', 'Some comment');
    }
}

/**
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class LogEffortSpec_DomainDriver extends drivers\DomainDriver {

    private $events;

    public function givenTheTask($task) {
        $this->givenTheNextUidIs($task);
        $this->service->handle(new CreateGoal($task));
        $this->service->handle(new AddTask("Goal-$task", $task));
    }

    public function whenILogEffortFor_From_Until($task, $start, $end) {
        $this->events = $this->service->handle(new LogEffort("Task-$task", new \DateTimeImmutable($start), new \DateTimeImmutable($end)));
    }

    public function whenILogEffortFor_WithComment($task, $comment) {
        $this->events = $this->service->handle(new LogEffort("Task-$task", new \DateTimeImmutable(), new \DateTimeImmutable('1 hour'), $comment));
    }

    public function whenITryToLogEffortFor($task) {
        $this->whenITryToLogEffortFor_From_Until($task, 'now', '1 second');
    }

    public function whenITryToLogEffortFor_From_Until($task, $start, $end) {
        $this->try->tryTo(function () use ($task, $start, $end) {
            $this->whenILogEffortFor_From_Until($task, $start, $end);
        });
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function thenAnEffortShouldBeLoggedFor_Starting_Ending($task, $start, $end) {
        $this->assert->contains($this->events, new EffortLogged("Task-$task", new \DateTimeImmutable($start), new \DateTimeImmutable($end)));
    }

    public function thenAnEffortShouldBeLoggedFor_WithComment($task, $comment) {
        $this->assert->size(array_filter($this->events, function ($event) use ($task, $comment) {
            return $event instanceof EffortLogged
            && $event->getTask() == "Task-$task"
            && $event->getComment() == $comment;
        }), 1);
    }
}