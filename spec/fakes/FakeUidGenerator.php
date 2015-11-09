<?php namespace spec\rtens\ucdi\fakes;

use rtens\ucdi\es\UidGenerator;

class FakeUidGenerator extends UidGenerator {

    private $count = 1;

    private $next;

    public function generate($prefix = null) {
        return $prefix . '-' . ($this->next ?: $this->count++);
    }

    public function setCount($next) {
        $this->next = $next;
    }
}