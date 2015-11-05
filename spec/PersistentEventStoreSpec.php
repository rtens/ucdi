<?php namespace spec\rtens\ucdi;

use rtens\ucdi\es\Event;
use rtens\ucdi\es\PersistentEventStore;

/**
 * @property \rtens\scrut\Assert assert <-
 */
class PersistentEventStoreSpec {

    private $dir;
    private $file;

    /** @var \rtens\ucdi\es\EventStore */
    private $store;

    function before() {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ucdi-test';
        $this->file = $this->dir . DIRECTORY_SEPARATOR . uniqid() . '.json';

        $this->store = new PersistentEventStore($this->file);
    }

    function createFolder() {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ucdi-test-');
        $file = $dir . DIRECTORY_SEPARATOR . 'file';

        new PersistentEventStore($file);

        $this->assert->isTrue(file_exists($dir));
        $this->assert->not(file_exists($file));
    }

    function saveEvents() {
        $this->store->save($this->getEvents());

        $this->assert->isTrue(file_exists($this->file));
        $this->assert->equals(file_get_contents($this->file), $this->getContent());
    }

    function appendEvents() {
        file_put_contents($this->file, $this->getContent());
        $this->store->save($this->getEvents());

        $this->assert->size(json_decode(file_get_contents($this->file), true), 4);
    }

    function loadFromNonExistentStore() {
        $store = new PersistentEventStore($this->file);
        $this->assert->equals($store->load(), []);
    }

    function loadEvents() {
        file_put_contents($this->file, $this->getContent());
        $events = $this->store->load();
        $this->assert->equals($events, $this->getEvents());
    }

    private function getContent() {
        return json_encode(
            [
                [
                    'class' => PersistentEventStoreSpec_Event::class,
                    'data' => [
                        'foo' => 'foo',
                        'bar' => [
                            'one' => 'uno'
                        ],
                        'created' => '2011-12-13T14:15:16+00:00',
                    ]
                ],
                [
                    'class' => PersistentEventStoreSpec_Event::class,
                    'data' => [
                        'foo' => 'bar',
                        'bar' => [
                            'two' => 'dos'
                        ],
                        'created' => '2011-12-13T14:15:16+00:00',
                    ]
                ]
            ], JSON_PRETTY_PRINT);
    }

    private function getEvents() {
        return [
            new PersistentEventStoreSpec_Event(new \DateTimeImmutable('2011-12-13 14:15:16 UTC'), 'foo', ['one' => 'uno']),
            new PersistentEventStoreSpec_Event(new \DateTimeImmutable('2011-12-13 14:15:16 UTC'), 'bar', ['two' => 'dos']),
        ];
    }
}

class PersistentEventStoreSpec_Event extends Event {

    /** @var string */
    private $foo;

    /** @var array|string */
    private $bar;

    /**
     * @param \DateTimeImmutable $created
     * @param string $foo
     * @param array $bar
     */
    public function __construct(\DateTimeImmutable $created, $foo, array $bar) {
        parent::__construct($created);
        $this->foo = $foo;
        $this->bar = $bar;
    }
}