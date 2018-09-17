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
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\WorkflowInterface\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Exception\LogicException;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Model\State;
use UniteCMS\CoreBundle\Model\StateSettings;
use UniteCMS\CoreBundle\Exception\InvalidStateSettingsPlacesException;
use UniteCMS\CoreBundle\Exception\InvalidStateSettingsTransitionsException;

class StateFieldType extends FieldType
{
    const TYPE = "state";
    const FORM_TYPE = ChoiceType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['initial_place', 'places', 'transitions'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['places', 'transitions'];

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
    function buildWorkflow(FieldableField $field) {

        $definitionBuilder = new DefinitionBuilder();
        $definition = $definitionBuilder->addPlaces($places);

        foreach ($field->getSettings()->transitions as $name => $transition) {
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

        #dump($field->getSettings());
        #dump($data); exit;

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

        try {
            $state_settings = new StateSettings($settings->places, $settings->transitions, $settings->initial_place);
        }
        catch (InvalidStateSettingsPlacesException $e) {
            $context->buildViolation($e->getMessage())->atPath('places')->addViolation();
        }
        catch (InvalidStateSettingsTransitionsException $e) {
            $context->buildViolation($e->getMessage())->atPath('transitions')->addViolation();
        }

    }

}