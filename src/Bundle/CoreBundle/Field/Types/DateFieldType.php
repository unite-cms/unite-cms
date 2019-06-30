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

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        return empty($value) ? null : (string)$value;
    }
}