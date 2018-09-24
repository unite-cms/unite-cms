<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;

use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\WorkflowInterface\InstanceOfSupportStrategy;

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
        $workflow = $this->buildWorkflow();
        if (!$workflow->can($this, $transition_to)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Function for building workflow out of settings
     *
     * @return Workflow
     */
    private function buildWorkflow()
    {
        $settings = $this->getSettings();

        $definitionBuilder = new DefinitionBuilder();
        $definitionBuilder->addPlaces(array_keys($settings['places']));

        foreach ($settings['transitions'] as $name => $transition) {
            $definitionBuilder->addTransition(new Transition($name, $transition['from'], $transition['to']));
        }

        $definition = $definitionBuilder->build();
        $marking = new SingleStateMarkingStore('state');
        return new Workflow($definition, $marking);
    }

}