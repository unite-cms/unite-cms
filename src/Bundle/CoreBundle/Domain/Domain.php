<?php


namespace UniteCMS\CoreBundle\Domain;

use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use UniteCMS\CoreBundle\DependencyInjection\Configuration;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Log\LogInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;

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
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var ContentTypeManager $contentTypeManager
     */
    protected $contentTypeManager;

    /**
     * @var string[] $schema
     */
    protected $schema = [];

    /**
     * @var string[] $parameters
     */
    protected $parameters = [];

    /**
     * @var null|string $editableSchemaFilesDirectory
     */
    protected $editableSchemaFilesDirectory = null;

    /**
     * @var int
     */
    protected $jwtTTLShortLiving;

    /**
     * @var int
     */
    protected $jwtTTLLongLiving;

    /**
     * Domain constructor.
     *
     * @param string $id
     * @param ContentManagerInterface $contentManager
     * @param UserManagerInterface $userManager
     * @param LoggerInterface $logger
     * @param string[] $schema
     * @param array $parameters
     * @param string $editableSchemaFilesDirectory
     * @param int $jwtTTLShortLiving
     * @param int $jwtTTLLongLiving
     * @param ContentTypeManager|null $contentTypeManager
     */
    public function __construct(
        string $id,
        ContentManagerInterface $contentManager,
        UserManagerInterface $userManager,
        LoggerInterface $logger,
        array $schema = [],
        array $parameters = [],
        string $editableSchemaFilesDirectory = null,
        int $jwtTTLShortLiving = Configuration::DEFAULT_JWT_TTL_SHORT_LIVING,
        int $jwtTTLLongLiving = Configuration::DEFAULT_JWT_TTL_LONG_LIVING,
        ContentTypeManager $contentTypeManager = null) {
        $this->id = $id;
        $this->contentManager = $contentManager;
        $this->userManager = $userManager;
        $this->logger = $logger;
        $this->schema = $schema;
        $this->parameters = $parameters;
        $this->editableSchemaFilesDirectory = $editableSchemaFilesDirectory;
        $this->jwtTTLShortLiving = $jwtTTLShortLiving;
        $this->jwtTTLLongLiving = $jwtTTLLongLiving;
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
     * @return LoggerInterface
     */
    public function getLogger() : LoggerInterface {
        return $this->logger;
    }

    /**
     * @param string $level
     * @param string $message
     * @param string $username
     *
     * @return LogInterface
     */
    public function log(string $level, string $message, string $username = null) : LogInterface {
        return $this->getLogger()->log($this, $level, $message, $username);
    }

    /**
     * @return string[]
     */
    public function getSchema() : array
    {
        return $this->schema;
    }

    /**
     * @return string[]
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * @return string[]
     */
    public function getCompleteSchema() : array
    {
        $schema = $this->getSchema();
        if($this->getEditableSchemaFilesDirectory()) {
            $schema = array_merge(
                $schema,
                DomainManager::findSchemaFilesInDir($this->getEditableSchemaFilesDirectory())
            );
        }

        // Replace domain parameters %(NAME)% => NAME
        $search = array_map(function($key){
            return '%(' . $key . ')%';
        }, array_keys($this->parameters));
        $replace = array_values($this->parameters);

        return array_map(function($schemaContent) use ($search, $replace) {
            return str_replace($search, $replace, $schemaContent);
        }, $schema);
    }

    /**
     * @return string|null
     */
    public function getEditableSchemaFilesDirectory() : ?string
    {
        if(empty($this->editableSchemaFilesDirectory)) {
            return null;
        }

        $suffix = substr($this->editableSchemaFilesDirectory, -1, 1) !== '/' ? '/' : '';
        return $this->editableSchemaFilesDirectory . $suffix;
    }

    /**
     * @return int
     */
    public function getJwtTTLShortLiving(): int {
        return $this->jwtTTLShortLiving;
    }

    /**
     * @return int
     */
    public function getJwtTTLLongLiving(): int {
        return $this->jwtTTLLongLiving;
    }
}
