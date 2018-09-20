<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 14.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * We use this model only for validation!
 */
class StateSettings
{

    /**
     * @var string
     * @Assert\Type(type="string", message="workflow_invalid_initial_place")
     * @Assert\NotBlank(message="workflow_invalid_initial_place")
     * @Assert\Choice(callback="getPlacesIdentifiers", message="workflow_invalid_initial_place")
     */
    private $initialPlace;

    /**
     * @var StatePlace[]
     * @Assert\Valid
     * @Assert\NotBlank(message="workflow_invalid_places")
     */
    private $places;

    /**
     * @var StateTransition[]
     * @Assert\Valid
     * @Assert\NotBlank(message="workflow_invalid_transitions")
     */
    private $transitions;

    const PLACES_KEYS = ['label', 'category'];
    const TRANSITIONS_KEYS = ['label', 'from', 'to'];

    /**
     * @param StatePlace[] $places
     * @param StateTransition[] $transitions
     * @param string $initialPlace
     */
    public function __construct(array $places, array $transitions, string $initialPlace)
    {
        $this->setPlaces($places);
        $this->setTransitions($transitions);
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
     * @return StatePlace[]
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * @return StateTransition[]
     */
    public function getTransitions()
    {
        return $this->transitions;
    }

    /**
     * @param string $initial_place
     */
    private function setInitialPlace(string $initial_place = null)
    {
        if (null === $initial_place) {
            return;
        }

        $this->initialPlace = $initial_place;
    }

    /**
     * @param StatePlace[] $places
     *
     * @return StateSettings
     */
    public function setPlaces(array $places)
    {
        $this->places = [];

        foreach ($places as $place) {
            $this->addPlace($place);
        }

        return $this;
    }

    /**
     * @param StatePlace $place
     */
    private function addPlace(StatePlace $place)
    {
        $this->places[] = $place;
    }


    /**
     * @param StateTransition[] $transitions
     *
     * @return StateSettings
     */
    public function setTransitions(array $transitions)
    {
        $this->transitions = [];

        foreach ($transitions as $transition) {
            
            $this->addTransition($transition);
        }

        return $this;
    }

    /**
     * @param StateTransition $state_transition
     */
    private function addTransition(StateTransition $state_transition)
    {
        $this->transitions[] = $state_transition;
    }

    /*
    * @return array
    */
    public function getPlacesIdentifiers() : array
    {
        $identifiers = [];

        foreach ($this->places as $place) {
            $identifiers[] = $place->getIdentifier();
        }

        return $identifiers;
    }

    /**
     * @param array $places
     * @param array $transitions
     * @param string $initial_place
     *
     * @return StateSettings
     */
    public static function createSettingsFromArray(array $places, array $transitions, string $initial_place)
    {
        $new_places = [];
        $new_transitions = [];

        #const PLACES_KEYS = ['label', 'category'];
        #const TRANSITIONS_KEYS = ['label', 'from', 'to'];

        foreach ($places as $key => $place)
        {




            if (!is_array($place))
            {
                $place = [];
            }

            foreach (self::PLACES_KEYS as $key) {

            }

            $place['category'] = (!isset($place['category'])) ? "" : $place['category'];
            $place['label'] = (!isset($place['label'])) ? "" : $place['label'];

            $new_places[] = new StatePlace($key, $place['label'], $place['category']);

        }

        foreach ($transitions as $key => $transition) {

            if (!is_array($transition))
            {
                $transition = [];
            }

            # check if things are set
            $transition['label'] = (!isset($transition['label'])) ? "" : $transition['label'];
            $transition['from'] = (!isset($transition['from'])) ? [] : $transition['from'];
            $transition['from'] = (is_string($transition['from']))? [ $transition['from'] ] : $transition['from'];
            $transition['to'] = (!isset($transition['to'])) ? "" : $transition['to'];

            $new_transitions[] = new StateTransition($key, $transition['label'], $transition['from'], $transition['to']);

        }

        return new StateSettings($new_places, $new_transitions, $initial_place);
    }

    /**
     * @Assert\Callback
     */
    public function validateTransitions(ExecutionContextInterface $context, $payload)
    {

        foreach ($this->getTransitions() as $transition) 
        {
            
            foreach ($transition->getFroms() as $place) 
            {
                if (!in_array($place, $this->getPlacesIdentifiers()))
                {
                    $context->buildViolation('workflow_invalid_transition_from')
                        ->atPath('transitions')
                        ->addViolation();

                }
            }

            if (!in_array($transition->getTo(), $this->getPlacesIdentifiers()))
            {
                $context->buildViolation('workflow_invalid_transition_to')
                    ->atPath('transitions')
                    ->addViolation();

            }

        }

    }

}