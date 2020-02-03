<?php

namespace UniteCMS\CoreBundle\Domain;

use LogicException;
use Symfony\Component\Finder\Finder;
use UniteCMS\CoreBundle\Content\ContentValidatorManager;
use UniteCMS\CoreBundle\Validator\ContentValidatorInterface;
use UniteCMS\CoreBundle\Validator\GenericContentValidatorConstraint;

class DomainManager
{
    /**
     * @var ContentValidatorManager
     */
    protected $contentValidatorManager;

    /**
     * @var array
     */
    protected $domainConfig = [];

    /**
     * @var array
     */
    protected $globalParameters = [];

    /**
     * @var string $isAdminExpression
     */
    protected $isAdminExpression;

    /**
     * @var Domain
     */
    protected $domain = null;

    public function __construct(ContentValidatorManager $contentValidatorManager, array $domainConfig = [], array $globalParameters = [], string $isAdminExpression = 'false')
    {
        $this->contentValidatorManager = $contentValidatorManager;
        $this->domainConfig = $domainConfig;
        $this->globalParameters = $globalParameters;
        $this->isAdminExpression = $isAdminExpression;
        $this->globalParameters['IS_ADMIN'] = $this->globalParameters['IS_ADMIN'] ?? $isAdminExpression;
    }

    /**
     * @param Domain|null $domain
     * @return $this
     */
    public function setCurrentDomain(?Domain $domain) : self {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @param string $schemaFile
     * @return null|string
     */
    static function getSchemaFromFile(string $schemaFile) : ?string {

        if(!file_exists($schemaFile)) {
            return null;
        }

        $pathInfo = pathinfo($schemaFile);

        if(empty($pathInfo['extension']) || $pathInfo['extension'] != 'graphql') {
            return null;
        }

        return file_get_contents($schemaFile);
    }

    /**
     * @param string $dir
     * @return array
     */
    static function findSchemaFilesInDir(string $dir) : array {

        $finder = new Finder();
        $finder->files()->in($dir)->name('*.graphql');
        $schemaFiles = [];

        foreach($finder as $file) {
            $schemaFiles[$file->getPathname()] = $file->getContents();
        }

        return $schemaFiles;
    }

    /**
     * @param array $schemaConfig
     * @return array
     */
    static function normalizeSchemaConfig(array $schemaConfig) : array {

        $normalizedConfig = [];

        foreach($schemaConfig as $config) {

            // If this is a direct schema string
            if(!file_exists($config)) {
                $normalizedConfig[] = $config;
                continue;
            }

            // If this is a schema file
            if($content = static::getSchemaFromFile($config)) {
                $normalizedConfig[$config] = $content;
                continue;
            }

            // If this is a folder, add all .graphql schema files.
            $normalizedConfig = array_merge($normalizedConfig, static::findSchemaFilesInDir($config));
        }

        return $normalizedConfig;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setCurrentDomainFromConfigId(string $id) : self {

        if(!empty($this->domain)) {
            return $this;
        }

        if(!isset($this->domainConfig[$id])) {
            throw new LogicException(sprintf('No domain with id "%s" found in domain configuration.', $id));
        }

        $config = $this->domainConfig[$id];

        $this->setCurrentDomain(new Domain(
            $id,
            $config['content_manager'],
            $config['user_manager'],
            $config['logger'],
            static::normalizeSchemaConfig($config['schema']),
            $this->globalParameters + ($config['parameters'] ?? []),
            array_map(function(ContentValidatorInterface $validator){
                return new GenericContentValidatorConstraint($validator);
            }, $this->contentValidatorManager->getContentValidatorsForDomain($id)),
            $config['editable_schema_files_directory'] ?? null,
            $config['jwt_ttl_short_living'],
            $config['jwt_ttl_long_living']
        ));

        return $this;
    }

    /**
     * @return Domain|null
     */
    public function current() : Domain {

        if(empty($this->domain)) {

            // If only one domain is configured, automatically use it.
            if(count($this->domainConfig) === 1) {
                $this->setCurrentDomainFromConfigId(array_key_first($this->domainConfig));
                return $this->domain;
            }

            throw new LogicException('You tried to access the current domain before it was set.');
        }

        return $this->domain;
    }

    /**
     * @return DomainManager
     */
    public function clearDomain() : DomainManager {
        $this->domain = null;
        return $this;
    }

    /**
     * @return array
     */
    public function getDomainConfig() : array {
        return $this->domainConfig;
    }

    /**
     * @return array
     */
    public function getGlobalParameters(): array
    {
        return $this->globalParameters;
    }

    /**
     * @param array $globalParameters
     * @return self
     */
    public function setGlobalParameters(array $globalParameters = []): self
    {
        $this->globalParameters = $globalParameters;
        return $this;
    }

    /**
     * @param string $key
     * @param string $parameter
     * @return self
     */
    public function setGlobalParameter(string $key, string $parameter) : self {
        $this->globalParameters[$key] = $parameter;
        return $this;
    }

    /**
     * @return string
     */
    public function getIsAdminExpression(): string
    {
        return $this->isAdminExpression;
    }
}
