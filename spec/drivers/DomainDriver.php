<?php namespace spec\rtens\ucdi\drivers;

use rtens\mockster\arguments\Argument as Arg;
use rtens\mockster\Mockster;
use rtens\scrut\Fixture;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\es\ApplicationService;
use rtens\ucdi\es\EventStore;
use spec\rtens\ucdi\fakes\FakeUidGenerator;
use watoki\curir\protocol\Url;

class DomainDriver extends Fixture {
    /** @var EventStore */
    private $store;

    /** @var Calendar */
    protected $calendar;

    /** @var ApplicationService */
    protected $service;

    /** @var Url */
    protected $baseUrl;

    public function before() {
        $this->calendar = Mockster::of(Calendar::class);
        $this->store = new EventStore();
        $this->baseUrl = Url::fromString('http://example.com/ucdi');

        $this->givenNowIs('now');

        Mockster::stub($this->calendar->insertEvent(Arg::any(), Arg::any(), Arg::any(), Arg::any()))
            ->will()->return_('CalendarEventId-1');
    }

    public function givenNowIs($when) {
        $this->service = new ApplicationService(
            new Application(new FakeUidGenerator(), Mockster::mock($this->calendar), $this->baseUrl, new \DateTimeImmutable($when)),
            $this->store);
    }
}