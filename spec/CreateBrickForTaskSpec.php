<?php namespace spec\rtens\ucdi;

use rtens\mockster\Mockster;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickScheduled;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property CreateBrickForTaskSpec_DomainDriver driver <-
 */
class CreateBrickForTaskSpec {

    function failIfStartsInThePast() {
        $this->driver->whenTryToIScheduleABrickFor('1 second ago');
        $this->driver->thenItShouldFailWith('Cannot schedule brick in the past');
    }

    function scheduleBrick() {
        $this->driver->whenISchedule_Of_For_MinutesStarting('Brick Foo', 'Task-1', 15, 'tomorrow 12:00');
        $this->driver->then_Of_ShouldBeScheduledFor_MinutesStarting('Brick Foo', 'Task-1', 15, 'tomorrow 12:00');
        $this->driver->thenAnAppointment_For_Starting_Ending_ShouldBeInsertedInMyCalendar('Brick Foo', 'Brick-1', 'tomorrow 12:00', 'tomorrow 12:15');
    }
}

/**
 * @property \rtens\scrut\Assert assert <-
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class CreateBrickForTaskSpec_DomainDriver extends DomainDriver {

    private $events;

    public function whenTryToIScheduleABrickFor($when) {
        $this->try->tryTo(function () use ($when) {
            $this->whenISchedule_Of_For_MinutesStarting('Brick Foo', 'Task-Foo', 0, $when);
        });
    }

    public function whenISchedule_Of_For_MinutesStarting($description, $taskId, $minutes, $start) {
        $this->events = $this->service->handle(new ScheduleBrick(
            $taskId,
            $description,
            new \DateTimeImmutable($start),
            new \DateInterval("PT{$minutes}M")));
    }

    public function thenItShouldFailWith($message) {
        $this->try->thenTheException_ShouldBeThrown($message);
    }

    public function then_Of_ShouldBeScheduledFor_MinutesStarting($description, $taskId, $minutes, $start) {
        $this->assert->contains($this->events, new BrickScheduled(
            'Brick-1',
            $taskId,
            $description,
            new \DateTimeImmutable($start),
            new \DateInterval("PT{$minutes}M")));
    }

    public function thenAnAppointment_For_Starting_Ending_ShouldBeInsertedInMyCalendar($caption, $brickId, $start, $end) {
        Mockster::stub($this->calendar->insertEvent($caption, new \DateTimeImmutable($start), new \DateTimeImmutable($end), 'Link to ' . $brickId))
            ->shouldHave()->beenCalled();
    }
}