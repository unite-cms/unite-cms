<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 16.10.18
 * Time: 12:16
 */

namespace UniteCMS\CoreBundle\Service;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;

class DomainConfigManager
{
    /**
     * @var string $domainConfigDir, The location to store domain configurations.
     */
    private $domainConfigDir;

    /**
     * @var DomainDefinitionParser $domainDefinitionParser
     */
    private $domainDefinitionParser;

    /**
     * @var Filesystem $filesystem
     */
    private $filesystem;

    public function __construct(string $domainConfigDir, DomainDefinitionParser $definitionParser, Filesystem $filesystem)
    {
        $this->domainConfigDir = $domainConfigDir;
        $this->domainDefinitionParser = $definitionParser;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the location to store domain configurations.
     *
     * @return string, Dir will always include a trailing slash.
     */
    public function getDomainConfigDir() : string {
        return $this->domainConfigDir . (substr($this->domainConfigDir, -1, 1) === '/' ? '' : '/');
    }

    /**
     * Returns the full path to the config file for a domain.
     *
     * @param Domain $domain
     * @return string
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     */
    public function getDomainConfigPath(Domain $domain) {

        if(empty($domain->getOrganization())) {
            throw new MissingOrganizationException('You can only process domains where the organization is not empty.');
        }

        if(empty($domain->getOrganization()->getIdentifier())) {
            throw new MissingOrganizationException('You can only process domains where the organization identifier is not empty.');
        }

        if(empty($domain->getIdentifier())) {
            throw new MissingDomainException('You can only process domains where the identifier is not empty.');
        }

        return $this->domainConfigDir . $domain->getOrganization()->getIdentifier() . '/' . $domain->getIdentifier() . '.json';
    }

    /**
     * Dumps the domain as a JSON file to the organization directory in the defined domain config location.
     *
     * @param Domain $domain
     * @param bool $forceOverride, If provided, an existing domain config file will be overridden.
     * @return bool, Returns true if domain was dumped successfully. False if file did exist and forceOverride was set to false.
     *
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     * @throws IOException if the file cannot be written
     */
    public function dumpDomainToConfig(Domain $domain, bool $forceOverride = false) : bool{
        $path = $this->getDomainConfigPath($domain);

        if($this->filesystem->exists($path) && !$forceOverride) {
            return false;
        }

        $this->filesystem->dumpFile($path, $this->domainDefinitionParser->serialize($domain));

        return true;
    }

    /**
     * Reads the domain configuration JSON file and updates the given domain to it.
     *
     * @param Domain $domain
     * @throws MissingOrganizationException
     * @throws MissingDomainException
     * @throws InvalidDomainConfigurationException
     */
    public function updateDomainFromConfig(Domain $domain) : void {

        $path = $this->getDomainConfigPath($domain);

        // If file does not exists, return.
        if(!$this->filesystem->exists($path)) {
            throw new IOException(sprintf('Failed to load content from file "%s".', $path), 0, null, $path);
        }

        // Load config from file.
        $loadedConfig = @file_get_contents($path);

        if(!$loadedConfig) {
            throw new IOException(sprintf('Failed to load content from file "%s".', $path), 0, null, $path);
        }

        // Create domain object from config.
        $loadedDomain = $this->domainDefinitionParser->parse($loadedConfig);

        // Make sure, that the loaded domain has the same identifier as the given domain.
        if($loadedDomain->getIdentifier() !== $domain->getIdentifier()) {
            throw new InvalidDomainConfigurationException('The domain configuration identifier does not match with the filename.');
        }

        // Override domain with content from loadedDomain.
        $domain->setFromEntity($loadedDomain);
    }
}
