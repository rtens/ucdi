<?php namespace spec\rtens\ucdi;

/**
 * @property CreateGoalSpec_DomainDriver driver <-
 */
class CreateGoalSpec {

    function minimalGoal() {
        $this->driver->whenICreateTheGoal('Test');
        $this->driver->thenAGoal_ShouldHaveBeenCreated('Test');
    }

}

interface CreateGoalSpec_Driver {

    public function whenICreateTheGoal($name);

    public function thenAGoal_ShouldHaveBeenCreated($name);
}

/**
 * @property \rtens\scrut\Assert assert <-
 */
class CreateGoalSpec_DomainDriver implements CreateGoalSpec_Driver {

    public function whenICreateTheGoal($name) {
//        $this->events = $this->app->execute(new CreateGoal($name));
        $this->assert->incomplete();
    }

    public function thenAGoal_ShouldHaveBeenCreated($name) {
//        $this->assert->contains($this->events, new GoalCreated("$name-ID", $name));
        $this->assert->incomplete();
    }
}