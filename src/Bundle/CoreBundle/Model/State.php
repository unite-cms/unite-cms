<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;


/**
 * this model holds the current statuse (place), needed to work with symfony workflow
 */
class State
{
    /**
     * @var string
     */
    public $state;

    /**
     * @var array
     */
    private $settings;

    public function __construct($state)
    {
        $this->setState($state);
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return State
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param array $settings
     *
     * @return State
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get Settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Check if state can transist
     *
     * @param string $transition_to
     *
     * @return bool
     */
    public function canTransist(string $transition_to) : bool
    {
        $settings = $this->getSettings();
        $current_state = $this->getState();

        // transitions not set
        if (!isset($settings['transitions'][$transition_to]))
        {
            return FALSE;
        }

        $transition = $settings['transitions'][$transition_to];

        if (in_array($current_state, $transition['from']))
        {
            return TRUE;
        }

        return FALSE;
    }

}