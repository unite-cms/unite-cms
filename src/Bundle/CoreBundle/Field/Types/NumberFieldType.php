<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class NumberFieldType extends FieldType
{
    const TYPE = "number";
    const FORM_TYPE = NumberType::class;

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'numeric', 'message' => 'invalid_initial_data']))
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::float();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::float();
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        return ($value === null) ? null : (float)$value;
    }
}