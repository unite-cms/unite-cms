<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 31.08.18
 * Time: 14:32
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\WorkflowInterface\InstanceOfSupportStrategy;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Model\State;
use UniteCMS\CoreBundle\Model\StatePlace;
use UniteCMS\CoreBundle\Model\StateTransition;
use UniteCMS\CoreBundle\Model\StateSettings;

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

    private $validator;
    private $entityManager;
    private $translator;

    function __construct(
        ValidatorInterface $validator,
        EntityManager $entityManager,
        TranslatorInterface $translator
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
                [
                    'placeholder' => $this->translator->trans('state.field.placeholder'),
                    'choices' => $this->getChoices($field),
                    'label' => $this->translator->trans('state.field.label'),
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
            $choices[$transition['label']] = $transition['to'];
        }

        return $choices;
    }

    /**
     * @param FieldableField $field
     *
     * @return Workflow
     *
     */
    function buildWorkflow(FieldableField $field) {

        $definitionBuilder = new DefinitionBuilder();
        $definitionBuilder->addPlaces(array_keys($field->getSettings()->places));

        foreach ($field->getSettings()->transitions as $name => $transition) {
            $definitionBuilder->addTransition(new Transition($name, $transition['from'], $transition['to']));
        }

        $definition = $definitionBuilder->build();

        $marking = new SingleStateMarkingStore('state');
        return new Workflow($definition, $marking);
    }

    /**
     * {@inheritdoc}
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

        #dump($context->getObject()); exit;

        $old_object = $this->entityManager->getUnitOfWork()->getOriginalEntityData($context->getObject());

        $new_state = $data;

        #dump($field);
        #dump($data);
        #dump($old_object);
        #dump($context);
        #exit;
        #dump($context->getObject()); exit;

        $state = new State('draft');

        $workflow = $this->buildWorkflow($field);

        #if (!$workflow->can($state, "review")) {
        #    $context->buildViolation('workflow_transition_not_allowed')->atPath('['.$field->getIdentifier().']')->addViolation();
        #}
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

        // initial place must be a string
        if(!is_string($settings->initial_place)) {
            $context->buildViolation('workflow_invalid_initial_place')->atPath('initial_place')->addViolation();
            return;
        }

        // places must be an array.
        if(!is_array($settings->places)) {
            $context->buildViolation('workflow_invalid_places')->atPath('places')->addViolation();
            return;
        }

        // transitions must be a array.
        if(!is_array($settings->transitions)) {
            $context->buildViolation('workflow_invalid_transitions')->atPath('transitions')->addViolation();
            return;
        }


        $state_settings = StateSettings::createSettingsFromArray($settings->places, $settings->transitions, $settings->initial_place);
        dump($state_settings);
        exit;

        $errors = $this->validator->validate($state_settings);

        if (count($errors) > 0) {

           foreach ($errors as $error) 
           {
                $context->buildViolation($error->getMessageTemplate())->atPath($error->getPropertyPath())->addViolation();
           }
    
        }
    }

    /**
     * @param FieldableFieldSettings $settings
     *
     * @return StateSettings
     */
    private function createStateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context) 
    {




    }

}