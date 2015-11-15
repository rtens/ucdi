<?php namespace rtens\ucdi\app\queries;

class ListMissedBricks {

    /** @var null|\DateInterval */
    private $maxAge;

    /**
     * @param \DateInterval|null $maxAge
     */
    public function __construct(\DateInterval $maxAge = null) {
        $this->maxAge = $maxAge;
    }

    public function getMaxAge() {
        return $this->maxAge;
    }
}