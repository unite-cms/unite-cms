<?php


namespace UniteCMS\CoreBundle\Domain;

use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use UniteCMS\CoreBundle\DependencyInjection\Configuration;
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
     * @var ContentTypeManager $contentTypeManager
     */
    protected $contentTypeManager;

    /**
     * @var string[] $schema
     */
    protected $schema;

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
     * @param string[] $schema
     * @param int $jwtTTLShortLiving
     * @param int $jwtTTLLongLiving
     * @param ContentTypeManager|null $contentTypeManager
     */
    public function __construct(
        string $id,
        ContentManagerInterface $contentManager,
        UserManagerInterface $userManager,
        array $schema,
        int $jwtTTLShortLiving = Configuration::DEFAULT_JWT_TTL_SHORT_LIVING,
        int $jwtTTLLongLiving = Configuration::DEFAULT_JWT_TTL_LONG_LIVING,
        ContentTypeManager $contentTypeManager = null) {
        $this->id = $id;
        $this->contentManager = $contentManager;
        $this->userManager = $userManager;
        $this->schema = $schema;
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
     * @return string[]
     */
    public function getSchema() : array
    {
        return array_map(function($fileOrString){

            // If this is a direct schema string
            if(!file_exists($fileOrString)) {
                return $fileOrString;
            }

            $pathInfo = pathinfo($fileOrString);
            $schemaFiles = [];

            // If this is a single .graphql schema file.
            if(!empty($pathInfo['extension']) && $pathInfo['extension'] = 'graphql') {
                $schemaFiles[] = file_get_contents($fileOrString);
            }

            // If this is a folder, add all .graphql schema files.
            if(is_dir($fileOrString)) {
                foreach (scandir($fileOrString) as $file) {
                    $filePath = $fileOrString . '/'. $file;
                    $pathInfo = pathinfo($filePath);

                    if(!empty($pathInfo['extension']) && $pathInfo['extension'] = 'graphql') {
                        $schemaFiles[] = file_get_contents($filePath);
                    }
                }
            }

            return join("\n", $schemaFiles);

        }, $this->schema);
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
