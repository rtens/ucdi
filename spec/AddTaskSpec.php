<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property AddTaskSpec_DomainDriver driver <-
 */
class AddTaskSpec {

    function goalMustExist() {
        $this->driver->whenTryToIAdd_To('Task Foo', 'Goal-Foo');
        $this->driver->thenItShouldFailWith('Goal [Goal-Foo] does not exist.');
    }

    function addSingleTask() {
        $this->driver->givenAGoal();
        $this->driver->whenIAdd_To('Task Foo', 'Goal-1');
        $this->driver->then_ShouldBeAddedTo('Task Foo', 'Goal-1');
    }

    function addTaskWithDependency() {
        $this->driver->givenAGoal();
        $this->driver->whenIAdd_DependingOn_To('Task Foo', 'Task-Bar', 'Goal-1');
        $this->driver->then_ShouldBeMadeDependentOn('Task-2', 'Task-Bar');
    }
}

/**
 * @property \rtens\scrut\Assert assert <-
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class AddTaskSpec_DomainDriver extends DomainDriver {

    private $events;

    public function givenAGoal() {
        $this->service->handle(new CreateGoal('Foo'));
    }

    public function whenIAdd_To($description, $goalId) {
        $this->events = $this->service->handle(new AddTask($goalId, $description));
    }

    public function then_ShouldBeAddedTo($description, $goalId) {
        $this->assert->contains($this->events, new TaskAdded('Task-2', $goalId, $description));
    }

    public function whenIAdd_DependingOn_To($description, $dependencyTaskId, $goalId) {
        $this->events = $this->service->handle(new AddTask($goalId, $description, $dependencyTaskId));
    }

    public function then_ShouldBeMadeDependentOn($taskId, $dependencyTaskId) {
        $this->assert->contains($this->events, new TaskMadeDependent($taskId, $dependencyTaskId));
    }

    public function whenTryToIAdd_To($description, $goalId) {
        $this->try->tryTo(function () use ($description, $goalId) {
            $this->whenIAdd_To($description, $goalId);
        });
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }
}