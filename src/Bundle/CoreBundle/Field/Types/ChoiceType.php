<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class ChoiceType extends AbstractFieldType
{
    const TYPE = 'choice';

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        // Validate return type.
        if(empty($field->getEnumValues())) {
            $context
                ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use an GraphQL enum!')
                ->setParameter('{{ type }}', static::getType())
                ->setParameter('{{ return_type }}', $field->getReturnType())
                ->addViolation();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return $field->getReturnType();
    }
}
