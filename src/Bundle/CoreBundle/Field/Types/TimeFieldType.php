<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\TimeType;

class TimeFieldType extends FieldType
{
    const TYPE = "time";
    const FORM_TYPE = TimeType::class;
    const DATE_FORMAT = 'H:i:00';

    const SETTINGS = ['not_empty', 'description', 'default', 'form_group', 'min', 'max', 'step'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'widget' => 'single_text',
                'input' => 'string',
                'min' => $field->getSettings()->min ?? null,
                'max' => $field->getSettings()->max ?? null,
                'step' => $field->getSettings()->step ?? null,
            ]
        );
    }

    protected function transformDefaultValue($value) {
        if(empty($value)) {
            return null;
        }

        if(strtolower($value) === 'now') {
            return (new \DateTime('now'))->format(static::DATE_FORMAT);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return $this->transformDefaultValue($field->getSettings()->default);
    }

    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $value = $this->transformDefaultValue($value);
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Time(['message' => 'invalid_initial_data']))
        );
    }

    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        parent::validateSettings($settings, $context);
        $settingsArray = $settings ? get_object_vars($settings) : [];

        if (!empty($settingsArray['min'])) {
            if ($context->getValidator()
                    ->validate($settingsArray['min'], new Assert\Time(['message' => 'no_time_value']))
                    ->count() > 0
            ) {
                $context->buildViolation('no_time_value')->atPath('min')->addViolation();
            }
        }

        if (!empty($settingsArray['max'])) {
            if ($context->getValidator()
                    ->validate($settingsArray['max'], new Assert\Time(['message' => 'no_time_value']))
                    ->count() > 0
            ) {
                $context->buildViolation('no_time_value')->atPath('max')->addViolation();
            }
        }
    }

}
