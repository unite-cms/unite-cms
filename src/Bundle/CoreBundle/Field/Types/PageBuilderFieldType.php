<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\ArrayCollection;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class PageBuilderFieldType extends AbstractFieldType
{
    const TYPE = 'pageBuilder';
    const GRAPHQL_INPUT_TYPE = 'JSON';



    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        $returnTypes = empty($field->getUnionTypes()) ? [$field->getReturnType()] : array_keys($field->getUnionTypes());

        // TODO: Validate interface.
        //dump($returnTypes);

        // Validate return type.
        /*$allowedTypes = $this->allowedReturnTypes($field);
        if(!in_array($field->getReturnType(), $allowedTypes)) {
            $context
                ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use on of [{{ allowed_return_types }}].')
                ->setParameter('{{ type }}', static::getType())
                ->setParameter('{{ return_type }}', $field->getReturnType())
                ->setParameter('{{ allowed_return_types }}', join(', ', $allowedTypes))
                ->addViolation();
            return;
        }*/
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        // TODO
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {
        return new ArrayCollection([
            /*'customBlocks' => $field->getSettings()->get('customBlocks', []),
            'mediaType' => null,
            'templateType' => null,*/
        ]);
    }
}
