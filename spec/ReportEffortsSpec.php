<?php namespace spec\rtens\ucdi;
use rtens\ucdi\app\model\DateTimeSpan;
use rtens\ucdi\app\queries\ReportEfforts;

/**
 * @property ReportEffortsSpec_DomainDriver driver <-
 * @property \rtens\scrut\Assert assert <-
 */
class ReportEffortsSpec {

    function noEfforts() {
        $this->driver->whenIReportAllEfforts();
        $this->driver->thenThereShouldBe_Efforts(0);
    }

    function showAllEffortsOrderedByStart() {
        $this->driver->givenATask('Foo');
        $this->driver->givenATask('Bar');
        $this->driver->givenTheEffortFor_From_Until('Foo', 'now', '1 hour');
        $this->driver->givenTheEffortFor_From_Until_WithComment('Bar', '2 hours ago', '1 hour', 'Foo!');
        $this->driver->givenTheEffortFor_From_Until('Foo', '1 hour ago', 'now');

        $this->driver->whenIReportAllEfforts();
        $this->driver->thenThereShouldBe_Efforts(3);

        $this->driver->thenEffort_ShouldHaveTheGoal(1, 'Goal Bar');
        $this->driver->thenEffort_ShouldHaveTheTask(1, 'Bar');
        $this->driver->thenEffort_ShouldHaveTheComment(1, 'Foo!');
        $this->driver->thenEffort_ShouldStart(1, '2 hours ago');
        $this->driver->thenEffort_ShouldEnd(1, '1 hour');

        $this->driver->thenEffort_ShouldStart(2, '1 hour ago');
        $this->driver->thenEffort_ShouldStart(3, 'now');
    }

    function filterByGoal() {
        $this->driver->givenATask('Foo');
        $this->driver->givenATask('Bar');
        $this->driver->givenTheEffortFor('Foo');
        $this->driver->givenTheEffortFor('Bar');
        $this->driver->givenTheEffortFor('Foo');

        $this->driver->whenIReportTheEffortsSpentOfTheGoal('Bar');
        $this->driver->thenThereShouldBe_Efforts(1);
        $this->driver->thenEffort_ShouldHaveTheGoal(1, 'Goal Bar');
    }

    function filterByTimeSpan() {
        $this->driver->givenATask('Foo');
        $this->driver->givenTheEffortFor_From_Until('Foo', '1:00', '3:00');
        $this->driver->givenTheEffortFor_From_Until('Foo', '2:00', '3:00');
        $this->driver->givenTheEffortFor_From_Until('Foo', '2:00', '4:00');

        $this->driver->whenIReportTheEffortsSpentBetween_And('1:00', '4:00');
        $this->driver->thenThereShouldBe_Efforts(1);
        $this->driver->thenEffort_ShouldStart(1, '2:00');
        $this->driver->thenEffort_ShouldEnd(1, '3:00');
    }

    function calculateTotal() {
        $this->driver->givenATask('Foo');
        $this->driver->givenTheEffortFor_From_Until('Foo', '1:00', '2:30');
        $this->driver->givenTheEffortFor_From_Until('Foo', '1:00', '4:00');

        $this->driver->whenIReportAllEfforts();
        $this->driver->thenTheTotalShouldBe('4:30');
    }
}

class ReportEffortsSpec_DomainDriver extends drivers\DomainDriver {

    private $report;

    public function givenATask($task) {
        $this->givenTheNextUidIs($task);
        $this->service->handle(new \rtens\ucdi\app\commands\CreateGoal("Goal $task"));
        $this->service->handle(new \rtens\ucdi\app\commands\AddTask("Goal-$task", $task));
    }

    public function givenTheEffortFor($task) {
        $this->givenTheEffortFor_From_Until($task, 'now', '1 hour');
    }

    public function givenTheEffortFor_From_Until_WithComment($task, $start, $end, $comment) {
        $this->service->handle(new \rtens\ucdi\app\commands\LogEffort("Task-$task",
            new \DateTimeImmutable($start), new \DateTimeImmutable($end), $comment));
    }

    public function givenTheEffortFor_From_Until($task, $start, $end) {
        $this->givenTheEffortFor_From_Until_WithComment($task, $start, $end, null);
    }

    public function whenIReportAllEfforts() {
        $this->report = $this->service->execute(new ReportEfforts());
    }

    public function whenIReportTheEffortsSpentOfTheGoal($goal) {
        $this->report = $this->service->execute((new ReportEfforts())->setGoal("Goal-$goal"));
    }

    public function whenIReportTheEffortsSpentBetween_And($start, $end) {
        $this->report = $this->service->execute((new ReportEfforts())
            ->setTimeSpan(new DateTimeSpan(new \DateTimeImmutable($start), new \DateTimeImmutable($end))));
    }

    public function thenThereShouldBe_Efforts($count) {
        $this->assert->size($this->report['efforts'], $count);
    }

    public function thenEffort_ShouldHaveTheTask($pos, $task) {
        $this->assert->equals($this->report['efforts'][$pos - 1]['task'], $task);
    }

    public function thenEffort_ShouldHaveTheGoal($pos, $goal) {
        $this->assert->equals($this->report['efforts'][$pos - 1]['goal'], $goal);
    }

    public function thenEffort_ShouldStart($pos, $start) {
        $this->assert->equals($this->report['efforts'][$pos - 1]['start'], new \DateTimeImmutable($start));
    }

    public function thenEffort_ShouldEnd($pos, $end) {
        $this->assert->equals($this->report['efforts'][$pos - 1]['end'], new \DateTimeImmutable($end));
    }

    public function thenEffort_ShouldHaveTheComment($pos, $comment) {
        $this->assert->equals($this->report['efforts'][$pos - 1]['comment'], $comment);
    }

    public function thenTheTotalShouldBe($duration) {
        /** @var \DateInterval $total */
        $total = $this->report['total'];
        $this->assert->equals($total->format('%h:%I'), $duration);
    }
}