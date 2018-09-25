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
use Symfony\Component\Validator\Validation;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Model\StatePlace;
use UniteCMS\CoreBundle\Model\StateTransition;

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
     * @Assert\Type(type="array", message="workflow_invalid_place")
     */
    private $places;

    /**
     * @var StateTransition[]
     * @Assert\Valid
     * @Assert\NotBlank(message="workflow_invalid_transitions")
     * @Assert\Type(type="array", message="workflow_invalid_transition")
     */
    private $transitions;

    /*
     * @var array
     */
    private $settings;

    /**
     * @param $places
     * @param $transitions
     * @param $initialPlace
     */
    public function __construct($places, $transitions, $initialPlace)
    {
        $this->places = $places;
        $this->transitions = $transitions;
        $this->initialPlace = $initialPlace;
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
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     *
     * @return StateSettings
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
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
     * @param array $settings
     *
     * @return StateSettings
     */
    public static function createFromArray(array $settings)
    {
        $new_places = [];
        $new_transitions = [];

        foreach ($settings['places'] as $key => $place)
        {

            if (!is_array($place)
                or !isset($place['label']))
            {
                continue;
            }

            if (!isset($place['category']))
            {
                $place['category'] = "";
            }

            $new_places[] = new StatePlace($key, $place['label'], $place['category']);

        }

        foreach ($settings['transitions'] as $key => $transition)
        {

            if (!is_array($transition)
                or !isset($transition['label'])
                or !isset($transition['from'])
                or !isset($transition['to']))
            {
                continue;
            }

            $new_transitions[] = new StateTransition($key, $transition['label'], $transition['from'], $transition['to']);

        }

        $self = new self($new_places, $new_transitions, $settings['initial_place']);
        $self->setSettings($settings);
        return $self;
    }

    /**
     * @Assert\Callback
     */
    public function validateSettings(ExecutionContextInterface $context, $payload)
    {
        $settings = $this->getSettings();

        $cnt = 0;
        foreach ($settings['places'] as $key => $place)
        {
            
            if (!is_array($place))
            {
                $context->buildViolation('workflow_invalid_places')
                        ->atPath('places['.$cnt.']')
                        ->addViolation();

                $place = [];
            }

            foreach ($place as $kkey => $config) {

                if (!in_array($kkey, ['label', 'category'])) {
                    $context->buildViolation('workflow_invalid_place')
                        ->atPath('places['.$cnt.']')
                        ->addViolation();
                }

            }

            $cnt++;
        }

        $cnt = 0;
        foreach ($settings['transitions'] as $key => $transition)
        {

            if (!is_array($transition))
            {
                $context->buildViolation('workflow_invalid_transitions')
                        ->atPath('transitions['.$cnt.']')
                        ->addViolation();

                $transition = [];
            }

            foreach ($transition as $kkey => $config) {

                if (!in_array($kkey, ['label', 'from', 'to'])) {
                    $context->buildViolation('workflow_invalid_transition')
                        ->atPath('transitions['.$cnt.']')
                        ->addViolation();
                }

            }

            $cnt++;

        }

    }

    /**
     * @Assert\Callback
     */
    public function validateTransitions(ExecutionContextInterface $context, $payload)
    {

        foreach ($this->getTransitions() as $key => $transition)
        {
            
            foreach ($transition->getFroms() as $place) 
            {
                if (!in_array($place, $this->getPlacesIdentifiers()))
                {
                    $context->buildViolation('workflow_invalid_transition_from')
                        ->atPath('transitions['.$key.']')
                        ->addViolation();

                }
            }

            if (!in_array($transition->getTo(), $this->getPlacesIdentifiers()))
            {
                $context->buildViolation('workflow_invalid_transition_to')
                    ->atPath('transitions['.$key.']')
                    ->addViolation();

            }

        }

    }

}