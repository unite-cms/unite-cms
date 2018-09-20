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

    /**
     * @param StatePlace[] $places
     * @param StateTransition[] $transitions
     * @param string $initialPlace
     */
    public function __construct(array $places, array $transitions, string $initialPlace = null)
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
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
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