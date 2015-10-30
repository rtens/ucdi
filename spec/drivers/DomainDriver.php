<?php namespace spec\rtens\ucdi\drivers;

use rtens\mockster\Mockster;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\es\CommandHandler;
use spec\rtens\ucdi\fakes\FakeUidGenerator;

class DomainDriver {

    /** @var Calendar */
    protected $calendar;

    /** @var CommandHandler */
    protected $handler;

    public function __construct() {
        $this->calendar = Mockster::of(Calendar::class);
        $this->handler = new CommandHandler(new Application(new FakeUidGenerator(), Mockster::mock($this->calendar)));
    }
}