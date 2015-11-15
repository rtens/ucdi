<?php namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\queries\ListMissedBricks;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property ListMissedBricksSpec_DomainDriver driver <-
 */
class ListMissedBricksSpec {

    function noBricks() {
        $this->driver->whenIListMissedBricks();
        $this->driver->thenThereShouldBe_Bricks(0);
    }

    function allBricksLaid() {
        $this->driver->givenALaidBrick('Foo');
        $this->driver->givenALaidBrick('Bar');
        $this->driver->whenIListMissedBricks();
        $this->driver->thenThereShouldBe_Bricks(0);
    }

    function missedBricks() {
        $this->driver->givenABrick_ScheduledIn('Seven', '7 minutes');
        $this->driver->givenALaidBrick('Laid');
        $this->driver->givenABrick_ScheduledIn('Two', '2 minutes');
        $this->driver->givenABrick_ScheduledIn('Fifteen', '15 minutes');
        $this->driver->givenABrick_ScheduledIn('Five', '5 minutes');

        $this->driver->givenNowIs('10 minutes');
        $this->driver->whenIListMissedBricks();
        $this->driver->thenThereShouldBe_Bricks(3);
        $this->driver->thenBrick_ShouldBe(1, 'Two');
        $this->driver->thenBrick_ShouldBe(2, 'Five');
        $this->driver->thenBrick_ShouldBe(3, 'Seven');
    }

    function restrictTimeFrame() {
        $this->driver->givenABrick_ScheduledIn('one', '59 hour');
        $this->driver->givenABrick_ScheduledIn('three', '1 hour');

        $this->driver->givenNowIs('25 hours');
        $this->driver->whenIListMissedBricksOfTheLast_Hours(24);
        $this->driver->thenThereShouldBe_Bricks(1);
        $this->driver->thenBrick_ShouldBe(1, 'three');
    }
}

class ListMissedBricksSpec_DomainDriver extends DomainDriver {

    private $bricks;

    public function givenALaidBrick($description) {
        $this->givenABrick_ScheduledIn($description, '5 minutes');
        $this->service->handle(new MarkBrickLaid("Brick-$description"));
    }

    public function givenABrick_ScheduledIn($description, $when) {
        $this->givenTheNextUidIs($description);

        $this->service->handle(new CreateGoal('Goal Foo'));
        $this->service->handle(new AddTask("Goal-$description", 'Task Foo'));
        $this->service->handle(new ScheduleBrick("Task-$description", $description, new \DateTimeImmutable($when), new \DateInterval('PT1H')));
    }

    public function whenIListMissedBricks() {
        $this->bricks = $this->service->execute(new ListMissedBricks());
    }

    public function whenIListMissedBricksOfTheLast_Hours($int) {
        $this->bricks = $this->service->execute(new ListMissedBricks(new \DateInterval("PT{$int}H")));
    }

    public function thenThereShouldBe_Bricks($count) {
        $this->assert->size($this->bricks, $count);
    }

    public function thenBrick_ShouldBe($pos, $description) {
        $this->assert->equals($this->bricks[$pos - 1]['id'], "Brick-$description");
        $this->assert->equals($this->bricks[$pos - 1]['description'], $description);
    }
}