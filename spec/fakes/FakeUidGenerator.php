<?php namespace spec\rtens\ucdi\fakes;

use rtens\ucdi\es\UidGenerator;

class FakeUidGenerator extends UidGenerator {

    private $count = 1;

    public function generate($prefix = null) {
        return $prefix . '-' . $this->count++;
    }

}