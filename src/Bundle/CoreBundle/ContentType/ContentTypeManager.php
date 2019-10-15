<?php


namespace UniteCMS\CoreBundle\ContentType;

class ContentTypeManager
{
    /**
     * @var ContentType[] $contentTypes
     */
    protected $contentTypes = [];

    /**
     * @var ContentType[] $embeddedContentTypes
     */
    protected $embeddedContentTypes = [];

    /**
     * @var ContentType[] $unionContentTypes
     */
    protected $unionContentTypes = [];

    /**
     * @return ContentType[]
     */
    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    /**
     * @param string $id
     * @return ContentType|null
     */
    public function getContentType(string $id): ?ContentType
    {
        return $this->contentTypes[$id] ?? null;
    }

    /**
     * @param ContentType $contentType
     * @return ContentTypeManager
     */
    public function registerContentType(ContentType $contentType): self
    {
        $this->contentTypes[$contentType->getId()] = $contentType;

        // Find and generate nested union types.
        foreach($contentType->getFields() as $field) {
            if(!empty($field->getUnionTypes())) {
                $unionType = new ContentType($field->getReturnType());

                foreach($field->getUnionTypes() as $type) {
                    $unionType->registerField(new ContentTypeField($type->name, $field->getType(), [], false, false, null, null, $type->name));
                }

                $this->unionContentTypes[$unionType->getId()] = $unionType;
            }
        }

        return $this;
    }

    /**
     * @return ContentType[]
     */
    public function getEmbeddedContentTypes(): array
    {
        return $this->embeddedContentTypes;
    }

    /**
     * @param string $id
     * @return ContentType|null
     */
    public function getEmbeddedContentType(string $id): ?ContentType
    {
        return $this->embeddedContentTypes[$id] ?? null;
    }

    /**
     * @param ContentType $contentType
     * @return ContentTypeManager
     */
    public function registerEmbeddedContentType(ContentType $contentType): self
    {
        $this->embeddedContentTypes[$contentType->getId()] = $contentType;
        return $this;
    }

    /**
     * @return \UniteCMS\CoreBundle\ContentType\ContentType[]
     */
    public function getUnionContentTypes(): array
    {
        return $this->unionContentTypes;
    }

    /**
     * @param string $id
     * @return \UniteCMS\CoreBundle\ContentType\ContentType|null
     */
    public function getUnionContentType(string $id): ?ContentType
    {
        return $this->unionContentTypes[$id] ?? null;
    }
}
