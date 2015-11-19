<?php namespace rtens\ucdi;

use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\menu\ActionMenuItem;
use rtens\domin\delivery\web\menu\CustomMenuItem;
use rtens\domin\delivery\web\menu\MenuGroup;
use rtens\domin\delivery\web\renderers\link\types\GenericLink;
use rtens\domin\delivery\web\renderers\tables\types\ArrayTable;
use rtens\domin\delivery\web\root\IndexResource;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\reflection\GenericObjectAction;
use rtens\ucdi\app\Application;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\es\ApplicationService;
use rtens\ucdi\es\PersistentEventStore;
use rtens\ucdi\es\UidGenerator;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\curir\WebDelivery;

class Bootstrapper {

    /** @var ApplicationService */
    private $handler;

    /** @var string */
    private $userId;

    public function __construct($userDir, $userId, Url $baseUrl, Calendar $calendar) {
        $this->userId = $userId;
        $this->handler = new ApplicationService(
            new Application(new UidGenerator(), $calendar, $baseUrl, new \DateTimeImmutable()),
            new PersistentEventStore($userDir . '/events.json'));
    }

    public function runWebApp() {
        WebDelivery::quickResponse(IndexResource::class, WebApplication::init(function (WebApplication $app) {
            $app->name = 'ucdi';
            $this->configureMenu($app);
            $this->registerActions($app);
            $this->registerLinks($app);
        }, WebDelivery::init()));
    }

    private function registerActions(WebApplication $app) {
        $this->addQuery($app, \rtens\ucdi\app\queries\ShowDashboard::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ShowBrickStatistics::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\CreateGoal::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\AddTask::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\ScheduleBrick::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\LogEffort::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\RateGoal::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ListGoals::class)
            ->setAfterExecute(function ($goals) {
                return (new ArrayTable($goals))
                    ->selectColumns(['name', 'rating', 'nextBrick', 'tasks'])
                    ->setFilter('tasks', function ($tasks) {
                        return $tasks ? new Element('span', ['title' => implode("\n", $tasks)], [count($tasks)]) : '';
                    });
            });
        $this->addQuery($app, \rtens\ucdi\app\queries\PlotGoals::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ReportEfforts::class)
            ->setAfterExecute(function ($report) {
                $report['efforts'] = (new ArrayTable($report['efforts']))
                    ->selectColumns(['goal', 'task', 'comment', 'start', 'duration'])
                    ->setFilter('start', function (\DateTimeImmutable $start) {
                        return $start->format('Y-m-d H:i');
                    });
                return $report;
            });
        $this->addQuery($app, \rtens\ucdi\app\queries\ShowGoalOfBrick::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ShowGoal::class)
            ->setAfterExecute(function ($goal) {
                $goal['incompleteTasks'] = array_map(function ($task) {
                    $task['bricks'] = (new ArrayTable($task['bricks']))
                        ->selectColumns(['description', 'start', 'duration']);
                    return $task;
                }, $goal['incompleteTasks']);

                return $goal;
            });
        $this->addCommand($app, \rtens\ucdi\app\commands\MarkBrickLaid::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\MarkTaskCompleted::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\MarkGoalAchieved::class);
        $this->addCommand($app, \rtens\ucdi\app\commands\CancelGoal::class);
        $this->addQuery($app, \rtens\ucdi\app\queries\ListMissedBricks::class)
            ->setAfterExecute(function ($bricks) {
                return (new ArrayTable($bricks))
                    ->selectColumns(['description', 'start']);
            });
        $this->addQuery($app, \rtens\ucdi\app\queries\ListUpcomingBricks::class)
            ->setAfterExecute(function ($bricks) {
                return (new ArrayTable($bricks))
                    ->selectColumns(['description', 'start'])
                    ->setFilter('start', function ($start) {
                        return self::toRelative(new \DateTimeImmutable($start));
                    });
            });
    }

    function addQuery(WebApplication $app, $queryClass) {
        return $this->addGenericObjectAction($app, function ($query) {
            return $this->handler->execute($query);
        }, $queryClass);
    }

    function addCommand(WebApplication $app, $commandClass) {
        return $this->addGenericObjectAction($app, function ($command) {
            return $this->handler->handle($command);
        }, $commandClass);
    }

    function addGenericObjectAction(WebApplication $app, callable $executer, $class) {
        $action = new GenericObjectAction($class, $app->types, $app->parser, $executer);
        $app->actions->add((new \ReflectionClass($class))->getShortName(), $action);
        return $action;
    }

    private function configureMenu(WebApplication $app) {
        $app->menu->setBrand($app->name);
        $app->menu->addRight((new MenuGroup($this->userId))
            ->add(new CustomMenuItem(function (WebRequest $request) {
                return new Element('a',
                    ['href' => $request->getContext()->withParameter('logout', '')],
                    ['Logout']);
            })));

        $app->menu->add(new ActionMenuItem($app->actions, 'ShowDashboard'));
        $app->menu->add(new ActionMenuItem($app->actions, 'CreateGoal'));
        $app->menu->add(new ActionMenuItem($app->actions, 'ReportEfforts'));
    }

    private function registerLinks(WebApplication $app) {
        $is = function ($type) {
            return function ($item) use ($type) {
                return is_array($item) && isset($item['id']) && substr($item['id'], 0, strlen($type)) == $type;
            };
        };
        $set = function ($key) {
            return function ($item) use ($key) {
                return [$key => $item['id']];
            };
        };

        $app->links->add(new GenericLink('ShowGoal', $is('Goal'), $set('goal')));
        $app->links->add(new GenericLink('RateGoal', $is('Goal'), $set('goal')));
        $app->links->add(new GenericLink('AddTask', $is('Goal'), $set('goal')));
        $app->links->add(new GenericLink('MarkGoalAchieved', $is('Goal'), $set('goal')));
        $app->links->add(new GenericLink('CancelGoal', $is('Goal'), $set('goal')));
        $app->links->add(new GenericLink('ReportEfforts', $is('Goal'), $set('goal')));
        $app->links->add(new GenericLink('ScheduleBrick', $is('Task'), $set('task')));
        $app->links->add(new GenericLink('LogEffort', $is('Task'), $set('task')));
        $app->links->add(new GenericLink('MarkTaskCompleted', $is('Task'), $set('task')));
        $app->links->add(new GenericLink('ShowGoalOfBrick', $is('Brick'), $set('brick')));
        $app->links->add(new GenericLink('MarkBrickLaid', $is('Brick'), $set('brick')));
    }

    private static function toRelative(\DateTimeInterface $dateTime, \DateTimeInterface $now = null) {
        $precision = 'minute';
        $now = $now ?: new \DateTimeImmutable();

        if ($dateTime == $now) {
            return 'now';
        }

        $diff = $dateTime->diff($now);

        if ($dateTime->format('Y-m-d') != $now->format('Y-m-d')) {
            $days = (new \DateTime($dateTime->format('Y-m-d')))->diff(new \DateTime($now->format('Y-m-d')))->days;

            if ($days == 1) {
                $dayString = 'tomorrow';
            } else if ($days < 7) {
                $dayString = $dateTime->format('l');
            } else {
                $dayString = $dateTime->format('Y-m-d');
            }

            return $dayString . ' at ' . $dateTime->format('H:i');
        }

        $times = [];
        foreach ([
                     'hour' => $diff->h,
                     'minute' => $diff->i,
                     'second' => $diff->s
                 ] as $unit => $value) {
            if ($value) {
                $times[] = $value . ' ' . $unit . ($value == 1 ? '' : 's');
            }

            if ($precision == $unit) {
                break;
            }
        }

        return 'in ' . implode(', ', $times);
    }
}