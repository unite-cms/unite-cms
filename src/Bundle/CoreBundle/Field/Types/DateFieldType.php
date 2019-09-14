<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;

class DateFieldType extends FieldType
{
    const TYPE = "date";
    const FORM_TYPE = DateType::class;
    const DATE_FORMAT = 'Y-m-d';

    const SETTINGS = ['not_empty', 'description', 'default', 'form_group', 'min', 'max', 'step'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $attr = [];

        if(!empty($field->getSettings()->step)) {
            $attr['step'] = $field->getSettings()->step;
        }

        if(!empty($field->getSettings()->min)) {
            $attr['min'] = $this->transformMinMaxValue($field->getSettings()->min);
        }

        if(!empty($field->getSettings()->max)) {
            $attr['min'] = $this->transformMinMaxValue($field->getSettings()->max);
        }

        return array_merge(
            parent::getFormOptions($field),
            [
                'widget' => 'single_text',
                'input' => 'string',
                'attr' => $attr,
            ]
        );
    }

    protected function transformMinMaxValue($value) {
        return $value;
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

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $value = $this->transformDefaultValue($value);

        $violations = $context->getValidator()->validate($value, new Assert\DateTime([
            'format' => static::DATE_FORMAT,
        ]));

        foreach($violations as $violation) {
            $context->buildViolation($violation->getMessage())->atPath('default')->addViolation();
        }
    }

    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        $violations = parent::validateSettings($settings, $context);
        $settingsArray = $settings ? get_object_vars($settings) : [];

        if (!empty($settingsArray['min'])) {

            $violations = $context->getValidator()->validate($settingsArray['min'], new Assert\DateTime([
                'format' => static::DATE_FORMAT,
            ]));

            foreach($violations as $violation) {
                $context->buildViolation($violation->getMessage())->atPath('min')->addViolation();
            }
        }

        if (!empty($settingsArray['max'])) {
            $violations = $context->getValidator()->validate($settingsArray['max'], new Assert\DateTime([
                'format' => static::DATE_FORMAT,
            ]));

            foreach($violations as $violation) {
                $context->buildViolation($violation->getMessage())->atPath('max')->addViolation();
            }
        }

        if (!empty($settingsArray['min']) && !empty($settingsArray['max'])) {

            $violations = $context->getValidator()->validate(
                $settingsArray['min'],
                new Assert\LessThanOrEqual(['value' => $settingsArray['max']])
            );

            foreach($violations as $violation) {
                $context->buildViolation($violation->getMessage())->atPath('min')->addViolation();
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
