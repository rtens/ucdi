<?php
namespace spec\rtens\ucdi;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\MarkBrickLaid;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\queries\ShowBrickStatistics;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property ShowStreaksSpec_DomainDriver driver <-
 */
class ShowBrickStatisticsSpec {

    function noBricks() {
        $this->driver->whenIShowTheStreaks();
        $this->driver->thenTheCurrentStreakShouldBe(0);
        $this->driver->thenTheLongestStreakShouldBe(0);
        $this->driver->thenTheTotalNumberShouldBe(0);
        $this->driver->thenTheLaidNumberShouldBe(0);
    }

    function missedBrick() {
        $this->driver->givenABrickScheduledIn('5 minutes');

        $this->driver->givenNowIs('tomorrow');
        $this->driver->whenIShowTheStreaks();
        $this->driver->thenTheCurrentStreakShouldBe(0);
        $this->driver->thenTheLongestStreakShouldBe(0);
        $this->driver->thenTheTotalNumberShouldBe(1);
        $this->driver->thenTheLaidNumberShouldBe(0);
    }

    function uninterruptedStreak() {
        $this->driver->givenALaidBrickScheduledIn('10 minutes');
        $this->driver->givenALaidBrickScheduledIn('20 minutes');
        $this->driver->givenALaidBrickScheduledIn('30 minutes');

        $this->driver->givenNowIs('tomorrow');
        $this->driver->whenIShowTheStreaks();
        $this->driver->thenTheCurrentStreakShouldBe(3);
        $this->driver->thenTheLongestStreakShouldBe(3);
    }

    function interruptedStreak() {
        $this->driver->givenALaidBrickScheduledIn('10 minutes');
        $this->driver->givenALaidBrickScheduledIn('20 minutes');
        $this->driver->givenALaidBrickScheduledIn('30 minutes');
        $this->driver->givenABrickScheduledIn('40 minutes');
        $this->driver->givenALaidBrickScheduledIn('50 minutes');
        $this->driver->givenALaidBrickScheduledIn('60 minutes');

        $this->driver->givenNowIs('2 hours');
        $this->driver->whenIShowTheStreaks();
        $this->driver->thenTheCurrentStreakShouldBe(2);
        $this->driver->thenTheLongestStreakShouldBe(3);
        $this->driver->thenTheTotalNumberShouldBe(6);
        $this->driver->thenTheLaidNumberShouldBe(5);
    }

    function ignoreUpcomingBricks() {
        $this->driver->givenALaidBrickScheduledIn_ForMinutes('10 minutes', 10);
        $this->driver->givenALaidBrickScheduledIn_ForMinutes('15 minutes', 10);
        $this->driver->givenALaidBrickScheduledIn_ForMinutes('30 minutes', 10);

        $this->driver->givenNowIs('21 minutes');
        $this->driver->whenIShowTheStreaks();
        $this->driver->thenTheTotalNumberShouldBe(1);
    }
}

class ShowStreaksSpec_DomainDriver extends DomainDriver {

    private $streaks;

    public function givenABrickScheduledIn($when) {
        $this->givenALaidBrickScheduledIn_ForMinutes($when, 1);
    }

    public function givenALaidBrickScheduledIn_ForMinutes($when, $minutes) {
        $this->givenTheNextUidIs($when);
        $this->service->handle(new CreateGoal('Foo'));
        $this->service->handle(new AddTask("Goal-$when", 'Task Foo'));
        $this->service->handle(new ScheduleBrick("Task-$when", 'Brick Foo',
            new \DateTimeImmutable($when), new \DateInterval('PT' . $minutes . 'M')));
    }

    public function givenALaidBrickScheduledIn($when) {
        $this->givenABrickScheduledIn($when);
        $this->service->handle(new MarkBrickLaid("Brick-$when"));
    }

    public function whenIShowTheStreaks() {
        $this->streaks = $this->service->execute(new ShowBrickStatistics());
    }

    public function thenTheCurrentStreakShouldBe($int) {
        $this->assert->equals($this->streaks['currentStreak'], $int);
    }

    public function thenTheLongestStreakShouldBe($int) {
        $this->assert->equals($this->streaks['longestStreak'], $int);
    }

    public function thenTheTotalNumberShouldBe($int) {
        $this->assert->equals($this->streaks['total'], $int);
    }

    public function thenTheLaidNumberShouldBe($int) {
        $this->assert->equals($this->streaks['laid'], $int);
    }
}