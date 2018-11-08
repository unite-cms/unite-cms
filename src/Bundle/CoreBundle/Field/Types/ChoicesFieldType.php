<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ChoicesFieldType extends ChoiceFieldType
{
    const TYPE = "choices";
    const SETTINGS = ['not_empty', 'description', 'default', 'choices'];

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'multiple' => true,
                'expanded' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue(ExecutionContextInterface $context, $value) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, [
                new Assert\Type(['type' => 'array', 'message' => 'invalid_initial_data']),
                new Assert\All(['constraints' => [new Assert\Type(['type' => 'string', 'message' => 'invalid_initial_data'])]])
            ])
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::listOf(Type::string());
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::listOf(Type::string());
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        return (array)$value;
    }

}
