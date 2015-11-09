<?php namespace spec\rtens\ucdi;

use rtens\mockster\Mockster;
use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickScheduled;
use rtens\ucdi\app\events\CalendarEventInserted;
use spec\rtens\ucdi\drivers\DomainDriver;

/**
 * @property ScheduleBrickSpec_DomainDriver driver <-
 */
class ScheduleBrickSpec {

    function taskMustExist() {
        $this->driver->whenTryToIScheduleABrickFor('Foo');
        $this->driver->thenItShouldFailWith('Task [Foo] does not exist.');
    }

    function failIfStartsInThePast() {
        $this->driver->whenTryToIScheduleABrick('1 second ago');
        $this->driver->thenItShouldFailWith('Cannot schedule brick in the past');
    }

    function scheduleBrick() {
        $this->driver->givenATask();
        $this->driver->whenISchedule_Of_For_MinutesStarting('Brick Foo', 'Task-2', 15, 'tomorrow 12:00');
        $this->driver->then_Of_ShouldBeScheduledFor_MinutesStarting('Brick Foo', 'Task-2', 15, 'tomorrow 12:00');
        $this->driver->thenAnAppointment_For_WithTheDescription_Starting_Ending_ShouldBeInsertedInMyCalendar(
            'Brick Foo', 'Brick-3', 'Mark as laid: http://example.com/ucdi/MarkBrickLaid?brickId=Brick-3',
            'tomorrow 12:00', 'tomorrow 12:15');
    }
}

/**
 * @property \rtens\scrut\Assert assert <-
 * @property \rtens\scrut\fixtures\ExceptionFixture try <-
 */
class ScheduleBrickSpec_DomainDriver extends DomainDriver {

    private $events;

    public function givenATask() {
        $this->service->handle(new CreateGoal('Goal Foo'));
        $this->service->handle(new AddTask('Goal-1', 'Task Foo'));
    }

    public function whenTryToIScheduleABrick($when) {
        $this->givenATask();
        $this->try->tryTo(function () use ($when) {
            $this->whenISchedule_Of_For_MinutesStarting('Brick Foo', 'Task-2', 0, $when);
        });
    }

    public function whenTryToIScheduleABrickFor($taskId) {
        $this->try->tryTo(function () use ($taskId) {
            $this->whenISchedule_Of_For_MinutesStarting('Brick Foo', $taskId, 0, '1 minute');
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
            'Brick-3',
            $taskId,
            $description,
            new \DateTimeImmutable($start),
            new \DateInterval("PT{$minutes}M")));
    }

    public function thenAnAppointment_For_WithTheDescription_Starting_Ending_ShouldBeInsertedInMyCalendar($caption, $brickId, $description, $start, $end) {
        Mockster::stub($this->calendar->insertEvent($caption, new \DateTimeImmutable($start), new \DateTimeImmutable($end), $description))
            ->shouldHave()->beenCalled();
        $this->assert->contains($this->events, new CalendarEventInserted($brickId, 'CalendarEventId-1', $description));
    }
}