<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Exception\InvalidStateSettingsPlacesException;
use UniteCMS\CoreBundle\Exception\InvalidStateSettingsTransitionsException;

/**
 * We use this model only for validation!
 */
class StateSettings
{

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     */
    private $initialPlace;

    /**
     * @var array
     * @Assert\NotBlank(message="not_blank")
     */
    private $places = array();

    /**
     * @var array
     * @Assert\NotBlank(message="not_blank")
     */
    private $transitions = array();

    /**
     * All places categories
     */
    const CATEGORIES = ['primary', 'notice', 'info','success', 'warning', 'error', 'danger'];

    /**
     * Required transition keys
     */
    const REQUIRED_TRANSITION_KEYS = ['label', 'from', 'to'];

    /**
     * @param array[] $places
     * @param array[] $transitions
     * @param string $initialPlace
     */
    public function __construct(array $places, array $transitions, string $initialPlace = null)
    {

        foreach ($places as $place => $config) {
            $this->addPlace($place, $config);
        }

        foreach ($transitions as $transition => $config) {
            $this->addTransition($transition, $config);
        }

        $this->setInitialPlace($initialPlace);
        
    }

    /**
     * @return string|null
     */
    public function getInitialPlace()
    {
        return $this->initialPlace;
    }

    /**
     * @return string[]
     */
    public function getPlaces(): array
    {
        return $this->places;
    }

    /**
     * @return string[]
     */
    public function getTransitions(): array
    {
        return $this->transitions;
    }

    private function setInitialPlace(string $place = null)
    {
        if (null === $place) {
            return;
        }

        if (!isset($this->places[$place])) {
            throw new InvalidStateSettingsPlacesException(sprintf('Place "%s" cannot be the initial place as it does not exist.', $place));
        }

        $this->initialPlace = $place;
    }

    private function addPlace(string $place, array $config)
    {

        if (isset($config['category']) && !in_array($config['category'], self::CATEGORIES)) 
        {
            throw new InvalidStateSettingsPlacesException(sprintf('Category "%s" is not a valid category.', $config['category']));
        }

        $this->places[$place] = $config;
    }

    private function addTransition(string $transition, array $config)
    {

        $config['from'] = (!is_array($config['from']))? array($config['from']):$config['from'];

        # check for all required keys
        $missing = array_diff_key(array_flip(self::REQUIRED_TRANSITION_KEYS), $config);
        if (!empty($missing))
        {
            throw new InvalidStateSettingsTransitionsException(sprintf('Missing Transition Settings.', $transition));
        }

        foreach ($config['from'] as $from) 
        {
            if (!isset($this->places[$from])) 
            {
                throw new InvalidStateSettingsTransitionsException(sprintf('Place "%s" referenced in from transition "%s" does not exist.', $from, $transition));
            }
        }

        if (!isset($this->places[$config['to']])) 
        {
                throw new InvalidStateSettingsTransitionsException(sprintf('Place "%s" referenced in to transition "%s" does not exist.', $config['to'], $transition));
        }

        $this->transitions[$transition] = $config;

    }

}