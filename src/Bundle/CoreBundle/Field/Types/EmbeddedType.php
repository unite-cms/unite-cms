<?php


namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedContent;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedFieldData;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class EmbeddedType extends AbstractFieldType
{
    const TYPE = 'embedded';

    /**
     * @var DomainManager
     */
    protected $domainManager;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(DomainManager $domainManager, FieldTypeManager $fieldTypeManager)
    {
        $this->domainManager = $domainManager;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return sprintf('%sInput', $field->getReturnType());
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        return new EmbeddedContent($fieldData->getId(), $fieldData->getType(), $fieldData->getData());
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeData(ContentTypeField $field, $inputData = null) : FieldData {

        $domain = $this->domainManager->current();

        if(!$contentType = $domain->getContentTypeManager()->getEmbeddedContentType($field->getReturnType())) {
            // TODO: Logging
            return null;
        }

        // TODO: Duplicate with MutationResolver
        $normalizedData = [];

        foreach($inputData as $id => $embeddedFieldData) {
            $field = $contentType->getField($id);
            $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
            $normalizedData[$id] = $fieldType->normalizeData($field, $embeddedFieldData);
        }

        return new EmbeddedFieldData(uniqid(), $contentType->getId(), $normalizedData);
    }
}
