<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\MarkGoalAchieved;
use rtens\ucdi\app\commands\MarkTaskCompleted;
use rtens\ucdi\app\commands\RateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\queries\ListGoals;
use rtens\ucdi\app\Rating;
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
        $this->driver->givenTheGoal('Bar');
        $this->driver->givenTheGoal('Foo');

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(2);

        $this->driver->thenGOal_ShouldHaveTheName(1, 'Bar');
        $this->driver->thenGoal_ShouldHaveNoNextBrick(1);

        $this->driver->thenGOal_ShouldHaveTheName(2, 'Foo');
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

    function onlyWithoutNextBrick() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_Of('Task Bar', 'Goal-1');
        $this->driver->givenTheBrick_For_Scheduled('Brick Bar', 'Task-2', '5 minutes');
        $this->driver->givenTheGoal('Bar');

        $this->driver->whenIListAllGoalsWithoutANextBrick();
        $this->driver->thenThereShouldBe_Goals(1);
    }

    function filterAchieved() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheGoal('Bar');
        $this->driver->givenTheGoal('Baz');
        $this->driver->givenTheGoal_IsAchieved('Goal-2');

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(2);

        $this->driver->whenIListAllAchievedGoals();
        $this->driver->thenThereShouldBe_Goals(1);
    }

    function showTasks() {
        $this->driver->givenTheGoal('Foo');
        $this->driver->givenTheTask_Of('Task Foo', 'Goal-1');
        $this->driver->givenTheTask_Of('Task Bar', 'Goal-1');
        $this->driver->givenTheTask_Of('Task Baz', 'Goal-1');
        $this->driver->givenTask_IsMarkedCompleted('Task-4');

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(1);
        $this->driver->thenGOal_ShouldHave_Tasks(1, 2);
    }

    function sortByQuadrant() {
        $this->driver->givenTheGoal('One');
        $this->driver->givenTheGoal('Four');
        $this->driver->givenTheGoal('Three');
        $this->driver->givenTheGoal('Two');
        $this->driver->givenTheGoal('Two A');
        $this->driver->givenTheGoal('Two B');

        $this->driver->given_IsRated('Goal-1', 10, 10);
        $this->driver->given_IsRated('Goal-2', 0, 0);
        $this->driver->given_IsRated('Goal-3', 10, 0);
        $this->driver->given_IsRated('Goal-4', 0, 10);
        $this->driver->given_IsRated('Goal-5', 3, 10);
        $this->driver->given_IsRated('Goal-6', 4, 10);

        $this->driver->whenIListAllGoals();
        $this->driver->thenThereShouldBe_Goals(6);
        $this->driver->thenGOal_ShouldHaveTheName(1, 'One');
        $this->driver->thenGOal_ShouldHaveTheName(2, 'Two B');
        $this->driver->thenGOal_ShouldHaveTheName(3, 'Two A');
        $this->driver->thenGOal_ShouldHaveTheName(4, 'Two');
        $this->driver->thenGOal_ShouldHaveTheName(5, 'Three');
        $this->driver->thenGOal_ShouldHaveTheName(6, 'Four');
    }
}

class ListGoalsWithNextBricksSpec_DomainDriver extends DomainDriver {

    private $goals;

    public function givenTheGoal($name) {
        $this->service->handle(new CreateGoal($name));
    }

    public function givenTheTask_Of($taskDescription, $goalId) {
        $this->service->handle(new AddTask($goalId, $taskDescription));
    }

    public function givenTheBrick_For_Scheduled($brickDescription, $taskId, $start) {
        $this->givenTheNextUidIs($brickDescription);
        $this->service->handle(new ScheduleBrick($taskId, $brickDescription, new \DateTimeImmutable($start), new \DateInterval('PT1M')));
        $this->givenTheNextUidIs(null);
    }

    public function givenTheBrick_IsLaid($brickId) {
        $this->service->handle(new MarkBrickLaid($brickId));
    }

    public function givenTheGoal_IsAchieved($goalId) {
        $this->service->handle(new MarkGoalAchieved($goalId));
    }

    public function givenTask_IsMarkedCompleted($taskId) {
        $this->service->handle(new MarkTaskCompleted($taskId));
    }

    public function given_IsRated($goalId, $urgency, $importance) {
        $this->service->handle(new RateGoal($goalId, new Rating($urgency, $importance)));
    }

    public function whenIListAllGoals() {
        $this->goals = $this->service->execute(new ListGoals());
    }

    public function whenIListAllGoalsWithoutANextBrick() {
        $this->goals = $this->service->execute(new ListGoals(true));
    }

    public function whenIListAllAchievedGoals() {
        $this->goals = $this->service->execute(new ListGoals(false, true));
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

    public function thenGOal_ShouldHave_Tasks($pos, $count) {
        $this->assert->size($this->goals[$pos - 1]['tasks'], $count);
    }
}