<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CancelGoal;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\events\GoalCancelled;
use rtens\ucdi\app\events\TaskMarkedCompleted;

/**
 * @property CancelGoalSpec_DomainDriver driver <-
 */
class CancelGoalSpec {

    function goalMustExist() {
        $this->driver->whenITryToCancel('Nope');
        $this->driver->thenItShouldFailWith('Goal [Goal-Nope] does not exist.');
    }

    function cancelGoal() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->whenICancel('Foo');
        $this->driver->then_ShouldBeCancelled('Foo');
    }

    function failIfGoalIsAlreadyCancelled() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->whenICancel('Foo');
        $this->driver->whenITryToCancel('Foo');
        $this->driver->thenItShouldFailWith('Goal [Goal-Foo] was already cancelled.');
    }

    function failIfGoalIsAlreadyAchieved() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenIMark_AsAchieved('Foo');
        $this->driver->whenITryToCancel('Foo');
        $this->driver->thenItShouldFailWith('Goal [Goal-Foo] is already achieved.');
    }

    function markAllTasksAsCompleted() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_Of('Foo', 'Foo');
        $this->driver->givenTheTask_Of('Bar', 'Foo');
        $this->driver->givenTheTask_Of('Baz', 'Foo');
        $this->driver->givenTask_IsMarkedCompleted('Task Baz');

        $this->driver->whenICancel('Foo');
        $this->driver->then_ShouldBeMarkedCompleted('Foo');
        $this->driver->then_ShouldBeMarkedCompleted('Bar');
        $this->driver->then_ShouldNotBeMarkedCompleted('Task Bar');
    }
}

/**
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class CancelGoalSpec_DomainDriver extends drivers\DomainDriver {

    private $events;

    public function givenTheGoal($name) {
        $this->givenTheNextUidIs($name);
        $this->service->handle(new CreateGoal($name));
    }

    public function givenIMark_AsAchieved($name) {
        $this->service->handle(new MarkGoalAchieved("Goal-$name"));
    }

    public function givenTheTask_Of($taskDescription, $goalName) {
        $this->givenTheNextUidIs($taskDescription);
        $this->service->handle(new AddTask("Goal-$goalName", $taskDescription));
    }

    public function givenTask_IsMarkedCompleted($description) {
    }

    public function whenICancel($name) {
        $this->events = $this->service->handle(new CancelGoal("Goal-$name"));
    }

    public function whenITryToCancel($name) {
        $this->try->tryTo(function () use ($name) {
            $this->whenICancel($name);
        });
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function then_ShouldBeCancelled($name) {
        $this->assert->contains($this->events, new GoalCancelled("Goal-$name", $this->now));
    }

    public function then_ShouldBeMarkedCompleted($taskDescription) {
        $this->assert->contains($this->events, new TaskMarkedCompleted("Task-$taskDescription", $this->now));
    }

    public function then_ShouldNotBeMarkedCompleted($taskDescription) {
        $this->assert->not()->contains($this->events, new TaskMarkedCompleted("Task-$taskDescription", $this->now));
    }
}