<?php
namespace rtens\ucdi;

use watoki\stores\file\FileStore;

class SettingsStore {

    private static $key = 'settings.json';

    /** @var FileStore */
    private $store;

    public function __construct($userDir) {
        $this->store = FileStore::forClass(Settings::class, $userDir);
    }

    /**
     * @return Settings
     */
    public function read() {
        if (!$this->store->hasKey(self::$key)) {
            return new Settings();
        }

        return $this->store->read(self::$key);
    }

    public function write(Settings $settings) {
        if ($this->store->hasKey(self::$key)) {
            $this->store->update($settings);
        } else {
            $this->store->create($settings, self::$key);
        }
    }
}