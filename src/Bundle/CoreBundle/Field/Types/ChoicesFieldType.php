<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
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
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'array', 'message' => 'invalid_initial_data']))
        );
        if($context->getViolations()->count() == 0) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($value, new Assert\All(['constraints' => [
                    new Assert\Type(['type' => 'string', 'message' => 'invalid_initial_data']),
                    new Assert\Choice(['choices' => $settings->choices])
                ]]))
            );
        }
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
