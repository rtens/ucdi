<?php namespace spec\rtens\ucdi\drivers;

use rtens\mockster\Mockster;
use rtens\scrut\Fixture;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\es\ApplicationService;
use spec\rtens\ucdi\fakes\FakeUidGenerator;

class DomainDriver extends Fixture {

    /** @var Calendar */
    protected $calendar;

    /** @var ApplicationService */
    protected $service;

    public function before() {
        $this->calendar = Mockster::of(Calendar::class);
        $this->service = new ApplicationService(new Application(new FakeUidGenerator(), Mockster::mock($this->calendar)));
    }
}