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

    function __construct(
        ValidatorInterface $validator,
        EntityManager $entityManager
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
                [
                    'empty_data' => $field->getSettings()->initial_place,
                    'choices' => $this->getChoices($field),
                    'label' => 'State',
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

        $old_object = $this->entityManager->getUnitOfWork()->getOriginalEntityData($context->getObject());

        $new_state = $data;

        dump($field);
        dump($data);
        dump($old_object);
        dump($context);
        exit;
        #dump($context->getObject()); exit;

        $state = new State('draft');

        $workflow = $this->buildWorkflow($field);

        #if (!$workflow->can($state, "review")) {
        #    $context->buildViolation('invalid_workflow_transition')->atPath('['.$field->getIdentifier().']')->addViolation();
        #}

        $data = 'draft12345';
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
            $context->buildViolation('invalid_initial_place')->atPath('initial_place')->addViolation();
            return;
        }

        // places must be an array.
        if(!is_array($settings->places)) {
            $context->buildViolation('invalid_places')->atPath('places')->addViolation();
            return;
        }

        // transitions must be a array.
        if(!is_array($settings->transitions)) {
            $context->buildViolation('invalid_transitions')->atPath('transitions')->addViolation();
            return;
        }


        $state_settings = $this->createStateSettings($settings, $context);

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
        $places = [];
        $transitions = [];
        $initial_place = (isset($settings->initial_place))? $settings->initial_place : null;

        foreach ($settings->places as $key => $place) 
        {
            # avoid illegal string offsets
            if (!is_array($place)) 
            {
                $place = [];
            }

            $place['category'] = (!isset($place['category'])) ? "" : $place['category'];

            $places[] = new StatePlace($key, $place['category']);
           
        }

        foreach ($settings->transitions as $key => $transition) {
            
            if (!is_array($transition)) 
            {
                $transition = [];
            }

            # avoid illegal string offsets
            $transition['label'] = (!isset($transition['label'])) ? "" : $transition['label'];
            $transition['from'] = (!isset($transition['from'])) ? [] : $transition['from'];
            $transition['from'] = (is_string($transition['from']))? [$transition['from']] : $transition['from'];
            $transition['to'] = (!isset($transition['to'])) ? "" : $transition['to'];
            
            $transitions[] = new StateTransition($key, $transition['label'], $transition['from'], $transition['to']);
            
        }

        return new StateSettings($places, $transitions, $initial_place);

    }

}