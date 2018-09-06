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
        foreach ($settings->places as $place)
        {
            # must be all strings
            if (!is_string($place)) {
                $context->buildViolation('invalid_places')->atPath('places')->addViolation();
                break;
            }
        }

        $required_transition_keys = ['label', 'from', 'to'];
        # validate transition elements
        foreach ($settings->transitions as $name => $transition)
        {
            # if no array
            if (!is_array($transition))
            {
                $context->buildViolation('invalid_transitions')->atPath('transitions')->addViolation();
                break;
            }

            # check for all required keys
            $missing = array_diff_key(array_flip($required_transition_keys), $transition);
            if (!empty($missing))
            {
                $context->buildViolation('invalid_transitions_keys_missing')->atPath('transitions')->addViolation();
                break;
            }

            # check if a single transition has the right types
            if (!is_string($transition['label'])
                or !is_string($transition['to'])
                or (!is_string($transition['from']) && !is_array($transition['from']))) {

                $context->buildViolation('invalid_transitions2323')->atPath('transitions')->addViolation();
                break;
            }

            # check if transition to exists in places
            if (!in_array($transition['to'], $settings->places))
            {
                $context->buildViolation('invalid_transition_to')->atPath('transitions')->addViolation();
                break;
            }

            # check if transition from exists in places
            $check_from = (!is_array($transition['from'])) ? array($transition['from']):$transition['from'];
            foreach ($check_from as $from)
            {
                if (!in_array($from, $settings->places))
                {
                    $context->buildViolation('invalid_transition_from')->atPath('transitions')->addViolation();
                    break;
                }
            }

        }

    }

}