<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\DateType;

class DateFieldType extends FieldType
{
    const TYPE = "date";
    const FORM_TYPE = DateType::class;

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

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Date(['message' => 'invalid_initial_data']))
        );
    }

    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        $violations = parent::validateSettings($settings, $context);
        $settingsArray = $settings ? get_object_vars($settings) : [];
        if (!empty($settingsArray['min'])) {
            if ($context->getValidator()
                    ->validate($settingsArray['min'], new Assert\Date())
                    ->count() > 0
            ) {
                $context->buildViolation('no_date_value')->atPath('min')->addViolation();
            }
        }

        if (!empty($settingsArray['max'])) {
            if ($context->getValidator()
                    ->validate($settingsArray['max'], new Assert\Date())
                    ->count() > 0
            ) {
                $context->buildViolation('no_date_value')->atPath('max')->addViolation();
            }
        }

        if (!empty($settingsArray['min']) && !empty($settingsArray['max'])) {
            if ($context->getValidator()
                    ->validate($settingsArray['min'], new Assert\LessThanOrEqual(['value' => $settingsArray['max']]))
                    ->count() > 0
            ) {
                $context->buildViolation('min_greater_than_max')->atPath('min')->addViolation();
            }
        }

        return $violations;
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        return empty($value) ? null : (string)$value;
    }
}