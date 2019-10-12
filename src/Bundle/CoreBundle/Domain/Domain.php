<?php


namespace UniteCMS\CoreBundle\Domain;

use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;

class Domain
{
    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var ContentManagerInterface $contentManager
     */
    protected $contentManager;

    /**
     * @var ContentTypeManager $contentTypeManager
     */
    protected $contentTypeManager;

    /**
     * @var string[] $schema
     */
    protected $schema;

    /**
     * Domain constructor.
     *
     * @param string $id
     * @param ContentManagerInterface $contentManager
     * @param string[] $schema
     * @param ContentTypeManager|null $contentTypeManager
     */
    public function __construct(string $id, ContentManagerInterface $contentManager, array $schema, ContentTypeManager $contentTypeManager = null)
    {
        $this->id = $id;
        $this->contentManager = $contentManager;
        $this->schema = $schema;
        $this->contentTypeManager = $contentTypeManager ?? new ContentTypeManager();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ContentManagerInterface
     */
    public function getContentManager(): ContentManagerInterface
    {
        return $this->contentManager;
    }

    /**
     * @return ContentTypeManager
     */
    public function getContentTypeManager(): ContentTypeManager
    {
        return $this->contentTypeManager;
    }

    /**
     * @return string[]
     */
    public function getSchema() : array
    {
        return $this->schema;
    }
}
