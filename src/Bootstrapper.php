<?php namespace rtens\ucdi;

use rtens\domin\delivery\web\root\IndexResource;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\reflection\GenericObjectAction;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\es\ApplicationService;
use rtens\ucdi\es\PersistentEventStore;
use rtens\ucdi\es\UidGenerator;
use watoki\curir\WebDelivery;

class Bootstrapper {

    /** @var ApplicationService */
    private $handler;

    public function __construct($userDir, Calendar $calendar) {
        $this->handler = new ApplicationService(
            new Application(new UidGenerator(), $calendar, new \DateTimeImmutable()),
            new PersistentEventStore($userDir . '/events.json'));
    }

    public function runWebApp() {
        WebDelivery::quickResponse(IndexResource::class, WebApplication::init(function (WebApplication $app) {
            $this->registerActions($app);
        }, WebDelivery::init()));
    }

    private function registerActions(WebApplication $app) {
        $this->addCommand($app, \rtens\ucdi\app\commands\CreateGoal::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\AddTask::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\ScheduleBrick::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ListGoals::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ShowGoal::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\MarkBrickLaid::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\MarkTaskCompleted::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\MarkGoalAchieved::class);
    }


    function addQuery(WebApplication $app, $queryClass) {
        $this->addGenericObjectAction($app, function ($query) {
            return $this->handler->execute($query);
        }, $queryClass);
    }

    function addCommand(WebApplication $app, $commandClass) {
        $this->addGenericObjectAction($app, function ($command) {
            return $this->handler->handle($command);
        }, $commandClass);
    }

    function addGenericObjectAction(WebApplication $app, callable $executer, $class) {
        $app->actions->add((new \ReflectionClass($class))->getShortName(),
            new GenericObjectAction($class, $app->types, $app->parser, $executer));
    }
}