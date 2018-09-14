<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 31.08.18
 * Time: 14:32
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\WorkflowInterface\InstanceOfSupportStrategy;
use UniteCMS\CoreBundle\Model\State;


class StateFieldType extends FieldType
{
    const TYPE = "state";
    const FORM_TYPE = ChoiceType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['initial_place', 'places', 'transitions'];

    /**
     * All places types
     */
    const TYPES = ['primary', 'notice', 'info','success', 'warning', 'danger'];

    /**
     * Required transition keys
     */
    const REQUIRED_TRANSITION_KEYS = ['label', 'from', 'to'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['initial_place', 'places', 'transitions'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
                [
                    'empty_data' => $field->getSettings()->initial_place,
                    'choices' => $this->getChoices($field),
                    'required' => true
                ]
        );
    }

    /**
     * @param FieldableField $field
     *
     * @return array
     */
    function getChoices(FieldableField $field) : array
    {
        $choices = [];

        foreach ($field->getSettings()->transitions as $transition_key => $transition) {
            $choices[$transition['label']] = $transition_key;
        }

        return $choices;
    }

    /**
     * @param string $current_state
     * @param array $places
     * @param array $transitions
     *
     * @return Workflow
     *
     */
    function buildWorkflow(string $current_state, array $places, array $transitions) {

        $definitionBuilder = new DefinitionBuilder();
        $definition = $definitionBuilder->addPlaces($places);

        foreach ($transitions as $name => $transition) {
            $definition->addTransition(new Transition($name, $transition['from'], $transition['to']));
        }

        $definition->build();

        $marking = new SingleStateMarkingStore($current_state);
        return new Workflow($definition, $marking);
    }

    /**
     * {@inheritdoc}
     *
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context)
    {

        // When deleting content, we don't need to validate data.
        if (strtoupper($context->getGroup()) === 'DELETE') {
            return;
        }

        // Only validate available data.
        if (empty($data)) {
            return;
        }

        $state = new State($data);

        dump($field->getSettings());
        dump($data); exit;

        #$context->buildViolation('missing_reference_definition')->atPath('['.$field->getIdentifier().']')->addViolation();

    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if ($context->getViolations()->count() > 0) {
            return;
        }

        # check if initial place exists inside places
        if (!is_string($settings->initial_place) or !in_array($settings->initial_place, array_keys($settings->places)))
        {
            $context->buildViolation('invalid_initial_place')->atPath('initial_place')->addViolation();
        }

        # check valid places

        $this->validPlaces($settings->places, $context);

        # check valid transitions
        $this->validTransitions($settings->transitions, $settings->places, $context);

    }

    /**
     * @param array $places
     * @param ExecutionContextInterface $context
     */
    private function validPlaces(array $places, ExecutionContextInterface &$context)
    {
        foreach ($places as $place => $place_config)
        {

            # must be all strings
            if (!is_string($place) or !is_array($place_config))
            {
                $context->buildViolation('invalid_places')->atPath('places')->addViolation();
                break;
            }

            # validate type
            if (isset($place_config['type']) && !in_array($place_config['type'], self::TYPES))
            {
                $context->buildViolation('invalid_places_types')->atPath('places')->addViolation();
                break;
            }

        }
    }

    /**
     * @param array transitions
     * @param array $places
     * @param ExecutionContextInterface $context
     */
    private function validTransitions(array $transitions, array $places, ExecutionContextInterface &$context)
    {
        foreach ($transitions as $name => $transition)
        {
            # if no array
            if (!is_array($transition))
            {
                $context->buildViolation('invalid_transitions')->atPath('transitions')->addViolation();
            }

            # check for all required keys
            $missing = array_diff_key(array_flip(self::REQUIRED_TRANSITION_KEYS), $transition);
            if (!empty($missing))
            {
                $context->buildViolation('invalid_transitions_keys_missing')->atPath('transitions')->addViolation();
            }

            # check if a single transition has the right types
            if (!isset($transition['label'])
                or !isset($transition['to'])
                or !isset($transition['from'])
                or !is_string($transition['label'])
                or !is_string($transition['to'])
                or (!is_string($transition['from']) && !is_array($transition['from'])))
            {

                $context->buildViolation('invalid_transitions')->atPath('transitions')->addViolation();
                break;
            }

            # check if transition to exists in places
            if (!in_array($transition['to'], array_keys($places)))
            {
                $context->buildViolation('invalid_transition_to')->atPath('transitions')->addViolation();
            }

            # check if transition from exists in places
            $check_from = (!is_array($transition['from'])) ? array($transition['from']):$transition['from'];
            foreach ($check_from as $from)
            {
                if (!in_array($from, array_keys($places)))
                {
                    $context->buildViolation('invalid_transition_from')->atPath('transitions')->addViolation();
                }
            }

        }
    }

}