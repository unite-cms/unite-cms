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
}
