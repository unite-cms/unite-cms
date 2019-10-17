<?php


namespace UniteCMS\CoreBundle\Domain;

use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use UniteCMS\CoreBundle\User\UserManagerInterface;

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
     * @var UserManagerInterface $userManager
     */
    protected $userManager;

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
     * @param UserManagerInterface $userManager
     * @param string[] $schema
     * @param ContentTypeManager|null $contentTypeManager
     */
    public function __construct(string $id, ContentManagerInterface $contentManager, UserManagerInterface $userManager, array $schema, ContentTypeManager $contentTypeManager = null)
    {
        $this->id = $id;
        $this->contentManager = $contentManager;
        $this->userManager = $userManager;
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
     * @return UserManagerInterface
     */
    public function getUserManager(): UserManagerInterface
    {
        return $this->userManager;
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
        return array_map(function($fileOrString){
            return substr($fileOrString, -8) === '.graphql' ? file_get_contents($fileOrString) : $fileOrString;
        }, $this->schema);
    }
}
