<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\queries\ListUpcomingBricks;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property ListUpcomingBricksSpec_DomainDriver driver <-
 */
class ListUpcomingBricksSpec {

    function noBricks() {
        $this->driver->whenIListUpcomingBricks();
        $this->driver->thenThereShouldBe_Bricks(0);
    }

    function alreadyLaid() {
        $this->driver->givenABrick('Foo');
        $this->driver->given_IsLaid('Foo');
        $this->driver->whenIListUpcomingBricks();
        $this->driver->thenThereShouldBe_Bricks(0);
    }

    function upcoming() {
        $this->driver->givenABrick_Scheduled('Ten', '10 minutes');
        $this->driver->givenABrick_Scheduled('Eight', '8 minutes');
        $this->driver->givenABrick_Scheduled('Five', '5 minutes');
        $this->driver->givenABrick_Scheduled('Nine', '9 minutes');

        $this->driver->givenNowIs('6 minutes');
        $this->driver->whenIListUpcomingBricks();
        $this->driver->thenThereShouldBe_Bricks(3);
        $this->driver->thenBrick_ShouldBe(1, 'Eight');
        $this->driver->thenBrick_ShouldBe(2, 'Nine');
        $this->driver->thenBrick_ShouldBe(3, 'Ten');
    }
}

class ListUpcomingBricksSpec_DomainDriver extends DomainDriver {

    private $bricks;

    public function givenABrick($description) {
        $this->givenABrick_Scheduled($description, '5 minutes');
    }

    public function givenABrick_Scheduled($description, $when) {
        $this->givenTheNextUidIs($description);
        $this->service->handle(new CreateGoal('Foo'));
        $this->service->handle(new AddTask("Goal-$description", 'Foo'));
        $this->service->handle(new \rtens\ucdi\app\commands\ScheduleBrick("Task-$description", $description, new \DateTimeImmutable($when), new \DateInterval('PT1H')));
    }

    public function given_IsLaid($description) {
        $this->service->handle(new MarkBrickLaid("Brick-$description"));
    }

    public function whenIListUpcomingBricks() {
        $this->bricks = $this->service->execute(new ListUpcomingBricks());
    }

    public function thenThereShouldBe_Bricks($count) {
        $this->assert->size($this->bricks, $count);
    }

    public function thenBrick_ShouldBe($pos, $description) {
        $this->assert->equals($this->bricks[$pos - 1]['id'], 'Brick-' . $description);
        $this->assert->equals($this->bricks[$pos - 1]['description'], $description);
    }
}