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
    const REQUIRED_SETTINGS = ['initial_place', 'places', 'transitions'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
                [
                    'initial_place' => $field->getSettings()->initial_place,
                    'places' => $field->getSettings()->places,
                    'transitions' => $field->getSettings()->transitions
                ]
        );
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

        # check if initial place is inside places
        if (!is_string($settings->initial_place) or !in_array($settings->initial_place, $settings->places))
        {
            $context->buildViolation('invalid_initial_place')->atPath('initial_place')->addViolation();
        }

        # check if places array is correct
        foreach ($settings->places as $place) {

            # must be all strings
            if (!is_string($place)) {
                $context->buildViolation('invalid_places')->atPath('invalid_places')->addViolation();
                break;
            }

        }

        $required_transition_keys = ['label', 'from', 'to'];

        # validate required transition elements
        foreach ($settings->transitions as $name => $transition) {

            # if no array
            if (!is_array($transition)) {
                $context->buildViolation('invalid_transitions')->atPath('invalid_transitions')->addViolation();
                break;
            }

            print_r(array_diff_key(array_flip($required_transition_keys), $transition));
            exit;


        }

    }

}