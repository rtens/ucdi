<?php namespace spec\rtens\ucdi;

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

        $this->store = new PersistentEventStore($this->file, new \DateTimeImmutable('2011-12-13 14:15:16 +00:00'));
    }

    function createFolder() {
        $file = sys_get_temp_dir() . '/' . uniqid('ucdi-test-') . '/file';

        $store = new PersistentEventStore($file);
        $store->save([]);

        $this->assert->isTrue(file_exists($file));
    }

    function saveEvents() {
        $this->store->save($this->getEvents());

        $this->assert->isTrue(file_exists($this->file));
        $this->assert->equals(file_get_contents($this->file), $this->getContent());
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

    function appendEvents() {
        file_put_contents($this->file, $this->getContent());
        $this->store->save($this->getEvents());

        $this->assert->size(json_decode(file_get_contents($this->file), true)['events'], 4);
    }

    private function getContent() {
        return json_encode([
            'events' => [
                [
                    'occurred' => '2011-12-13T14:15:16+00:00',
                    'event' => [
                        'type' => PersistentEventStoreSpec_Event::class,
                        'value' => [
                            'foo' => 'foo',
                            'bar' => [
                                'one' => 'uno'
                            ],
                        ]
                    ]
                ],
                [
                    'occurred' => '2011-12-13T14:15:16+00:00',
                    'event' => [
                        'type' => PersistentEventStoreSpec_Event::class,
                        'value' => [
                            'foo' => 'bar',
                            'bar' => [
                                'two' => 'dos'
                            ],
                        ]
                    ]
                ]
            ]
        ]);
    }

    private function getEvents() {
        return [
            new PersistentEventStoreSpec_Event('foo', ['one' => 'uno']),
            new PersistentEventStoreSpec_Event('bar', ['two' => 'dos']),
        ];
    }
}

class PersistentEventStoreSpec_Event {

    /** @var string */
    private $foo;

    /** @var array|string */
    private $bar;

    /**
     * @param string $foo
     * @param array $bar
     */
    public function __construct($foo, array $bar) {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}

