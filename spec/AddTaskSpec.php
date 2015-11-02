<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property AddTaskSpec_DomainDriver driver <-
 */
class AddTaskSpec {

    function addSingleTask() {
        $this->driver->whenIAdd_To('Task Foo', 'Goal-Foo');
        $this->driver->then_ShouldBeAddedTo('Task Foo', 'Goal-Foo');
    }

    function addTaskWithDependency() {
        $this->driver->whenIAdd_DependingOn('Task Foo', 'Task-Bar');
        $this->driver->then_ShouldBeMadeDependentOn('Task-1', 'Task-Bar');
    }
}

/**
 * @property \rtens\scrut\Assert assert <-
 */
class AddTaskSpec_DomainDriver extends DomainDriver {

    private $events;

    public function whenIAdd_To($description, $goalId) {
        $this->events = $this->service->handle(new AddTask($goalId, $description));
    }

    public function then_ShouldBeAddedTo($description, $goalId) {
        $this->assert->contains($this->events, new TaskAdded('Task-1', $goalId, $description));
    }

    public function whenIAdd_DependingOn($description, $dependencyTaskId) {
        $this->events = $this->service->handle(new AddTask('Goal-Foo', $description, $dependencyTaskId));
    }

    public function then_ShouldBeMadeDependentOn($taskId, $dependencyTaskId) {
        $this->assert->contains($this->events, new TaskMadeDependent($taskId, $dependencyTaskId));
    }
}