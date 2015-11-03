<?php namespace rtens\ucdi;

use rtens\domin\delivery\web\root\IndexResource;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\reflection\GenericObjectAction;
use rtens\mockster\Mockster;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\app\Time;
use rtens\ucdi\es\ApplicationService;
use rtens\ucdi\es\PersistentEventStore;
use rtens\ucdi\es\UidGenerator;
use watoki\curir\WebDelivery;

class Bootstrapper {

    public static function run($userDir) {
        $handler = new ApplicationService(
            new Application(new UidGenerator(), Mockster::mock(Calendar::class), new Time()),
            new PersistentEventStore($userDir . '/events.json'));

        $addCommand = function (WebApplication $app, $commandClass) use ($handler) {
            $app->actions->add((new \ReflectionClass($commandClass))->getShortName(), new GenericObjectAction($commandClass, $app->types, $app->parser,
                function ($command) use ($handler) {
                    return $handler->handle($command);
                }));
        };

        $addQuery = function (WebApplication $app, $queryClass) use ($handler) {
            $app->actions->add((new \ReflectionClass($queryClass))->getShortName(), new GenericObjectAction($queryClass, $app->types, $app->parser,
                function ($query) use ($handler) {
                    return $handler->execute($query);
                }));
        };

        WebDelivery::quickResponse(IndexResource::class, WebApplication::init(function (WebApplication $app) use ($addCommand, $addQuery) {
            $addCommand($app, \rtens\ucdi\app\commands\CreateGoal::class);
            $addCommand($app, \rtens\ucdi\app\commands\AddTask::class);
            $addCommand($app, \rtens\ucdi\app\commands\ScheduleBrick::class);

            $addQuery($app, \rtens\ucdi\app\queries\ListGoals::class);
            $addQuery($app, \rtens\ucdi\app\queries\ShowGoal::class);
        }, WebDelivery::init()));
    }

}