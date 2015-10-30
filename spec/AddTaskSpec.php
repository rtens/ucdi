<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\events\TaskAdded;

/**
 * @property AddTaskSpec_DomainDriver driver <-
 */
class AddTaskSpec {

    function addSingleTask() {
        $this->driver->whenIAdd_To('Task Foo', 'Goal-Foo');
        $this->driver->then_ShouldBeAddedTo('Task Foo', 'Goal-Foo');
    }
}

/**
 * @property \rtens\scrut\Assert assert <-
 */
class AddTaskSpec_DomainDriver {

    private $events;

    public function whenIAdd_To($description, $goalId) {
        $app = new \rtens\ucdi\app\Application(new \spec\rtens\ucdi\fakes\FakeUidGenerator());
        $this->events = $app->handleAddTask(new AddTask($goalId, $description));
    }

    public function then_ShouldBeAddedTo($description, $goalId) {
        $this->assert->contains($this->events, new TaskAdded('Task-1', $goalId, $description));
    }
}