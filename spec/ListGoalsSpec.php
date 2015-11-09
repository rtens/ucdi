<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\queries\ListGoals;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property ListGoalsWithNextBricksSpec_DomainDriver driver <-
 */
class ListGoalsSpec {

    function noGoals() {
        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(0);
    }

    function goalsWithoutBricks() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheGoal('Bar');

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(2);

        $this->driver->thenGOal_ShouldHaveTheName(1, 'Foo');
        $this->driver->thenGoal_ShouldHaveNoNextBrick(1);

        $this->driver->thenGOal_ShouldHaveTheName(2, 'Bar');
        $this->driver->thenGoal_ShouldHaveNoNextBrick(2);
    }

    function expiredBrick() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_Of('My Task', 'Goal-1');
        $this->driver->givenTheBrick_For_Scheduled('My Brick', 'Task-2', 'tomorrow 12:00');

        $this->driver->givenNowIs('tomorrow 12:01');
        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(1);
        $this->driver->thenGoal_ShouldHaveNoNextBrick(1);
    }

    function futureBrick() {
        $this->driver->givenNowIs('2011-12-13 14:15:16');
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_Of('My Task', 'Goal-1');
        $this->driver->givenTheBrick_For_Scheduled('My Brick', 'Task-2', '2011-12-14 12:00:12');

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(1);
        $this->driver->thenGoal_ShouldHaveTheNextBrick(1, 'My Brick @2011-12-14 12:00');
    }

    function multipleBricks() {
        $this->driver->givenNowIs('2011-12-13 14:15:16');
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_Of('My Task', 'Goal-1');
        $this->driver->givenTheBrick_For_Scheduled('Seventeen', 'Task-2', '2011-12-17 12:00');
        $this->driver->givenTheBrick_For_Scheduled('Fifteen', 'Task-2', '2011-12-15 12:00');
        $this->driver->givenTheBrick_IsLaid('Brick-Fifteen');
        $this->driver->givenTheBrick_For_Scheduled('Sixteen', 'Task-2', '2011-12-16 12:00');

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(1);
        $this->driver->thenGoal_ShouldHaveTheNextBrick(1, 'Sixteen @2011-12-16 12:00');
    }
}

class ListGoalsWithNextBricksSpec_DomainDriver extends DomainDriver {

    private $goals;

    public function whenIListAllGoals() {
        $this->goals = $this->service->execute(new ListGoals());
    }

    public function givenTheGoal($name) {
        $this->service->handle(new CreateGoal($name));
    }

    public function givenTheTask_Of($taskDescription, $goalId) {
        $this->service->handle(new AddTask($goalId, $taskDescription));
    }

    public function givenTheBrick_For_Scheduled($brickDescription, $taskId, $start) {
        $this->givenTheNextUidIs($brickDescription);
        $this->service->handle(new ScheduleBrick($taskId, $brickDescription, new \DateTimeImmutable($start), new \DateInterval('PT1M')));
    }

    public function givenTheBrick_IsLaid($brickId) {
        $this->service->handle(new MarkBrickLaid($brickId));
    }

    public function thenThereShouldBe_Goals($int) {
        $this->assert->size($this->goals, $int);
    }

    public function thenGOal_ShouldHaveTheName($pos, $name) {
        $this->assert->equals($this->goals[$pos - 1]['name'], $name);
    }

    public function thenGoal_ShouldHaveNoNextBrick($pos) {
        $this->assert->equals($this->goals[$pos - 1]['nextBrick'], null);
    }

    public function thenGoal_ShouldHaveTheNextBrick($pos, $brickDescriptionAndStart) {
        $this->assert->equals($this->goals[$pos - 1]['nextBrick'], $brickDescriptionAndStart);
    }
}