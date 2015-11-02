<?php namespace rtens\ucdi\app;

use rtens\ucdi\app\commands\AddTask;
use rtens\ucdi\app\commands\CreateGoal;
use rtens\ucdi\app\commands\ScheduleBrick;
use rtens\ucdi\app\events\BrickScheduled;
use rtens\ucdi\app\events\GoalCreated;
use rtens\ucdi\app\events\GoalNotesChanged;
use rtens\ucdi\app\events\TaskAdded;
use rtens\ucdi\app\events\TaskMadeDependent;
use rtens\ucdi\es\UidGenerator;

class ApplicationService {

    /** @var UidGenerator */
    private $uid;

    /** @var \DateTimeImmutable */
    private $now;

    /** @var Calendar */
    private $calendar;

    /**
     * @param UidGenerator $uid
     * @param Calendar $calendar
     */
    public function __construct(UidGenerator $uid, Calendar $calendar) {
        $this->uid = $uid;
        $this->calendar = $calendar;
        $this->now = new \DateTimeImmutable();
    }

    public function handleCreateGoal(CreateGoal $command) {
        $goalId = $this->uid->generate('Goal');

        $events = [
            new GoalCreated($goalId, $command->getName())
        ];

        if ($command->getNotes()) {
            $events[] = new GoalNotesChanged($goalId, $command->getNotes());
        }
        return $events;
    }

    public function handleAddTask(AddTask $command) {
        $taskId = $this->uid->generate('Task');

        $events = [
            new TaskAdded($taskId, $command->getGoal(), $command->getDescription())
        ];

        if ($command->getDependency()) {
            $events[] = new TaskMadeDependent($taskId, $command->getDependency());
        }

        return $events;
    }

    public function handleScheduleBrick(ScheduleBrick $command) {
        if ($command->getStart() < $this->now) {
            throw new \Exception('Cannot schedule brick in the past');
        }
        $brickId = $this->uid->generate('Brick');

        $this->calendar->insertEvent(
            $command->getDescription(),
            $command->getStart(),
            $command->getStart()->add($command->getDuration()),
            'Link to ' . $brickId);

        return [
            new BrickScheduled(
                $brickId,
                $command->getTask(),
                $command->getDescription(),
                $command->getStart(),
                $command->getDuration())
        ];
    }

}