<?php


namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedContent;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedFieldData;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class EmbeddedType implements FieldTypeInterface
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
    static function getType(): string {
        return self::TYPE;
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
    public function resolveField(string $fieldName, ContentInterface $content, ContentTypeField $field) {

        /**
         * @var EmbeddedFieldData $fieldData
         */
        if(!$fieldData = $content->getFieldData($fieldName)) {
            return null;
        }

        return new EmbeddedContent($fieldData->getId(), $fieldData->getType(), $fieldData->getData());
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeData(ContentTypeField $field, $fieldData = null): FieldData {

        $domain = $this->domainManager->current();

        if(!$contentType = $domain->getContentTypeManager()->getEmbeddedContentType($field->getReturnType())) {
            // TODO: Logging
            return null;
        }

        // TODO: Duplicate with MutationResolver
        $normalizedData = [];

        foreach($fieldData as $id => $embeddedFieldData) {
            $field = $contentType->getField($id);
            $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
            $normalizedData[$id] = $fieldType->normalizeData($field, $embeddedFieldData);
        }

        // TODO change this to uuid.
        return new EmbeddedFieldData(uniqid(), $contentType->getId(), $normalizedData);
    }
}
