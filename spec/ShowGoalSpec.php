<?php namespace spec\rtens\ucdi;

use rtens\domin\parameters\Html;
use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\commands\RateGoal;
use rtens\ucdi\app\queries\ShowGoal;
use rtens\ucdi\app\Rating;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property ShowGoalSpec_DomainDriver driver <-
 */
class ShowGoalSpec {

    function goalMustExist() {
        $this->driver->whenITryToShowTheGoal('Goal-Foo');
        $this->driver->thenItShouldFailWith('Goal [Goal-Foo] does not exist.');
    }

    function minimalGoal() {
        $this->driver->givenTheGoal_WithTheNotes('Foo', 'Foo Notes');

        $this->driver->whenIShowTheGoal('Goal-1');
        $this->driver->thenTheNameShouldBe('Foo');
        $this->driver->thenTheNotesShouldBe('Foo Notes');
    }

    function showRating() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenARatingOf_And_For(2, 7, 'Goal-1');

        $this->driver->whenIShowTheGoal('Goal-1');
        $this->driver->thenTheRatingShouldBe('delegate (U7,I2)');
    }

    function showTasksOfGoal() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_For('Bar', 'Goal-1');
        $this->driver->givenTheTask_For('Baz', 'Goal-1');

        $this->driver->whenIShowTheGoal('Goal-1');
        $this->driver->thenThereShouldBe_Tasks(2);
        $this->driver->thenTask_ShouldHaveTheId(1, 'Task-2');
        $this->driver->thenTask_ShouldHaveTheDescription(1, 'Bar');
        $this->driver->thenTask_ShouldHaveTheDescription(2, 'Baz');
    }

    function onlyShowUncompletedTasks() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_For('Bar', 'Goal-1');
        $this->driver->givenTheTask_For('Baz', 'Goal-1');
        $this->driver->givenTask_IsCompleted('Task-2');

        $this->driver->whenIShowTheGoal('Goal-1');
        $this->driver->thenThereShouldBe_Tasks(1);
        $this->driver->thenTask_ShouldHaveTheId(1, 'Task-3');
    }

    function showFutureBricks() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_For('Bar', 'Goal-1');
        $this->driver->givenTheBrick_For_Scheduled('A Brick', 'Task-2', '15 minutes');
        $this->driver->givenTheBrick_For_Scheduled('B Brick', 'Task-2', '5 minutes');
        $this->driver->givenTheBrick_For_Scheduled('C Brick', 'Task-2', '10 minutes');

        $this->driver->whenIShowTheGoal('Goal-1');
        $this->driver->thenTask_ShouldHave_Bricks(1, 3);
        $this->driver->thenBrick_OfTask_ShouldHaveTheDescription(1, 1, 'B Brick');
        $this->driver->thenBrick_OfTask_ShouldHaveTheDescription(2, 1, 'C Brick');
        $this->driver->thenBrick_OfTask_ShouldHaveTheDescription(3, 1, 'A Brick');
    }

    function doNotShowPastBricks() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_For('Bar', 'Goal-1');
        $this->driver->givenTheBrick_For_Scheduled('A Brick', 'Task-2', '20 minutes');
        $this->driver->givenTheBrick_For_Scheduled('B Brick', 'Task-2', '5 minutes');
        $this->driver->givenTheBrick_For_Scheduled('C Brick', 'Task-2', '15 minutes');

        $this->driver->givenNowIs('10 minutes');
        $this->driver->whenIShowTheGoal('Goal-1');
        $this->driver->thenTask_ShouldHave_Bricks(1, 2);
        $this->driver->thenBrick_OfTask_ShouldHaveTheDescription(1, 1, 'C Brick');
        $this->driver->thenBrick_OfTask_ShouldHaveTheDescription(2, 1, 'A Brick');
    }
}

/**
 * @property \rtens\scrut\Assert assert <-
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class ShowGoalSpec_DomainDriver extends DomainDriver {

    private $goal;

    public function givenTheGoal_WithTheNotes($name, $notes) {
        $this->service->handle(new CreateGoal($name, new Html($notes)));
    }

    public function givenTheGoal($name) {
        $this->givenTheGoal_WithTheNotes($name, null);
    }

    public function givenTheTask_For($description, $goalId) {
        $this->service->handle(new AddTask($goalId, $description));
    }

    public function givenTheBrick_For_Scheduled($description, $taskId, $start) {
        $this->service->handle(new \rtens\ucdi\app\commands\ScheduleBrick($taskId, $description, new \DateTimeImmutable($start), new \DateInterval('PT10M')));
    }

    public function whenIShowTheGoal($goalId) {
        $this->goal = $this->service->execute(new ShowGoal($goalId));
    }

    public function givenARatingOf_And_For($importance, $urgency, $goalId) {
        $this->service->handle(new RateGoal($goalId, new Rating($urgency, $importance)));
    }

    public function givenTask_IsCompleted($taskId) {
        $this->service->handle(new MarkTaskCompleted($taskId));
    }

    public function whenITryToShowTheGoal($goalId) {
        $this->try->tryTo(function () use ($goalId) {
            $this->whenIShowTheGoal($goalId);
        });
    }

    public function thenTheNotesShouldBe($notes) {
        $this->assert->equals($this->goal['notes'], new Html($notes));
    }

    public function thenTheNameShouldBe($name) {
        $this->assert->equals($this->goal['name'], $name);
    }

    public function thenThereShouldBe_Tasks($count) {
        $this->assert->size($this->goal['incompleteTasks'], $count);
    }

    public function thenTask_ShouldHaveTheId($pos, $taskId) {
        $this->assert->equals($this->goal['incompleteTasks'][$pos - 1]['id'], $taskId);
    }

    public function thenTask_ShouldHaveTheDescription($pos, $description) {
        $this->assert->equals($this->goal['incompleteTasks'][$pos - 1]['description'], $description);
    }

    public function thenTask_ShouldHave_Bricks($taskPos, $brickCount) {
        $this->assert->size($this->goal['incompleteTasks'][$taskPos - 1]['bricks'], $brickCount);
    }

    public function thenBrick_OfTask_ShouldHaveTheDescription($brickPos, $taskPos, $description) {
        $this->assert->equals($this->goal['incompleteTasks'][$taskPos - 1]['bricks'][$brickPos - 1]['description'], $description);
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function thenTheRatingShouldBe($quadrantAndNumbers) {
        $this->assert->equals($this->goal['rating'], $quadrantAndNumbers);
    }
}