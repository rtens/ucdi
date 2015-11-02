<?php namespace rtens\ucdi;

use rtens\domin\delivery\web\root\IndexResource;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\reflection\GenericObjectAction;
use rtens\mockster\Mockster;
use rtens\ucdi\app\ApplicationService;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\es\CommandHandler;
use rtens\ucdi\es\UidGenerator;
use watoki\curir\WebDelivery;

class Application {

    public static function run() {
        $handler = new CommandHandler(new ApplicationService(new UidGenerator(), Mockster::mock(Calendar::class)));

        $addCommand = function (WebApplication $app, $commandClass) use ($handler) {
            $app->actions->add((new \ReflectionClass($commandClass))->getShortName(), new GenericObjectAction($commandClass, $app->types, $app->parser,
                function ($command) use ($handler) {
                    return $handler->handle($command);
                }));
        };

        WebDelivery::quickResponse(IndexResource::class, WebApplication::init(function (WebApplication $app) use ($addCommand) {
            $addCommand($app, CreateGoal::class);
            $addCommand($app, AddTask::class);
            $addCommand($app, ScheduleBrick::class);
        }, WebDelivery::init()));
    }

}