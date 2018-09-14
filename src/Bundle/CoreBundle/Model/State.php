<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;

class State
{

    /**
     * @var string
     */
    private $state;

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

}