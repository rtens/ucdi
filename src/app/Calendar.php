<?php
namespace rtens\ucdi\app;

interface Calendar {

    /**
     * @param string $summary
     * @param \DateTimeImmutable $start
     * @param \DateTimeImmutable $end
     * @param null|string $description
     * @return string Event ID
     */
    public function insertEvent($summary, \DateTimeImmutable $start, \DateTimeImmutable $end, $description = null);
}