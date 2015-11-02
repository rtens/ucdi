<?php namespace spec\rtens\ucdi\drivers;

use rtens\mockster\Mockster;
use rtens\scrut\Fixture;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\app\Time;
use rtens\ucdi\es\ApplicationService;
use rtens\ucdi\es\EventStore;
use spec\rtens\ucdi\fakes\FakeUidGenerator;

class DomainDriver extends Fixture {

    /** @var Calendar */
    protected $calendar;

    /** @var ApplicationService */
    protected $service;

    /** @var Time */
    private $time;

    public function before() {
        $this->calendar = Mockster::of(Calendar::class);
        $this->time = Mockster::of(Time::class);
        Mockster::stub($this->time->now())->will()->return_(new \DateTimeImmutable());

        $this->service = new ApplicationService(
            new Application(new FakeUidGenerator(), Mockster::mock($this->calendar), Mockster::mock($this->time)),
            new EventStore());
    }

    public function givenNowIs($when) {
        Mockster::stub($this->time->now())->will()->return_(new \DateTimeImmutable($when));
    }
}