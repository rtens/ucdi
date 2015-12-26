<?php
namespace rtens\ucdi\app\commands;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\types\EnumerationType;
use rtens\ucdi\app\Calendar;
use rtens\ucdi\SettingsStore;
use watoki\reflect\type\NullableType;
use watoki\reflect\type\StringType;

class SelectCalendar implements Action {

    /** @var Calendar */
    private $calendar;

    /** @var SettingsStore */
    private $settings;

    public function __construct(SettingsStore $settings, Calendar $calendar) {
        $this->settings = $settings;
        $this->calendar = $calendar;
    }

    /**
     * @return string
     */
    public function caption() {
        return 'Select Calendar';
    }

    /**
     * @return string|null
     */
    public function description() {
        return null;
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        return [
            new Parameter('calendar', new NullableType(
                new EnumerationType($this->calendar->availableCalendars(), new StringType())), true)
        ];
    }

    /**
     * Fills out partially available parameters
     *
     * @param array $parameters Available values indexed by name
     * @return array Filled values indexed by name
     */
    public function fill(array $parameters) {
        $settings = $this->settings->read();
        if ($settings->calendarId) {
            $parameters['calendar'] = $settings->calendarId;
        }
        return $parameters;
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return mixed the result of the execution
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $settings = $this->settings->read();
        $settings->calendarId = $parameters['calendar'];
        $this->settings->write($settings);

        if (!$settings->calendarId) {
            return 'Calendar synchronization deactivated';
        }
        return 'Calendar set to [' . $settings->calendarId . ']';
    }

    /**
     * @return boolean True if the action modifies the state of the application
     */
    public function isModifying() {
        return true;
    }
}