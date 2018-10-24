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
use Symfony\Component\Finder\Finder;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
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
     * @param Organization $organization
     * @return string
     * @throws MissingOrganizationException
     */
    public function getOrganizationConfigPath(Organization $organization) : string {
        if(empty($organization->getIdentifier())) {
            throw new MissingOrganizationException('Organization identifier is empty.');
        }

        return $this->domainConfigDir . $organization->getIdentifier() . '/';
    }

    /**
     * Returns the full path to the config file for a domain.
     *
     * @param Domain $domain
     * @return string
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     */
    public function getDomainConfigPath(Domain $domain) : string {

        if(empty($domain->getOrganization())) {
            throw new MissingOrganizationException('You can only process domains where the organization is not empty.');
        }

        if(empty($domain->getIdentifier())) {
            throw new MissingDomainException('You can only process domains where the identifier is not empty.');
        }

        return $this->getOrganizationConfigPath($domain->getOrganization()) . $domain->getIdentifier() . '.json';
    }

    /**
     * Parses the config to an domain entity. This will change anything in the config dir.
     *
     * @param string $config
     * @return Domain
     */
    public function parse(string $config) : Domain {
        return $this->domainDefinitionParser->parse($config);
    }

    /**
     * Serializes the domain entity to an json string. This will change anything in the config dir.
     *
     * @param Domain $domain
     * @return string
     */
    public function serialize(Domain $domain) : string {
        return $this->domainDefinitionParser->serialize($domain);
    }

    /**
     * Dumps the domain as a JSON file to the organization directory in the defined domain config location.
     *
     * @param Domain $domain
     * @param bool $forceOverride , If provided, an existing domain config file will be overridden.
     *
     * @return bool, Returns true if domain was dumped successfully. False if file did exist and forceOverride was set to false.
     *
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     * @throws IOException if the file cannot be written
     * @throws InvalidDomainConfigurationException
     */
    public function updateConfig(Domain $domain, bool $forceOverride = false) : bool{
        $path = $this->getDomainConfigPath($domain);

        if($this->filesystem->exists($path) && !$forceOverride) {
            return false;
        }

        $serializedDomain = $this->serialize($domain);

        // If config is empty, just serialize the domain and save it.
        if(empty($domain->getConfig())) {
            $config = $serializedDomain;
        }

        // If config is not empty, first check if it is the same as the domain.
        else {
            $config = $domain->getConfig();
            $parsedConfig = $this->parse($config);

            if($serializedDomain !== $this->serialize($parsedConfig)) {
                throw new InvalidDomainConfigurationException('Domain config does not match parsed domain.');
            }
        }

        $this->filesystem->dumpFile($path, json_encode(json_decode($config), JSON_PRETTY_PRINT));
        return true;
    }

    /**
     * Returns true, if the domain config folder exists, also is trying to create the folder
     *
     * @return bool
     */
    public function configFolderExists() : bool {

        $config_path = $this->getDomainConfigDir();

        // if domain config folder was never created
        if (!$this->filesystem->exists($config_path)) {

            // try to create the domain config folder, will work only systems with the appropriate on the parent folder
            try
            {
                $this->filesystem->mkdir($config_path);
            }
            catch (IOException $exception)
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Returns true, if the config for the given domain exists.
     *
     * @param Domain $domain
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     * @return bool
     */
    public function configExists(Domain $domain) : bool {
        $path = $this->getDomainConfigPath($domain);
        return $this->filesystem->exists($path);
    }

    /**
     * Loads the domain configuration JSON file and optional updates the given domain to it.
     *
     * @param Domain $domain
     * @param bool $updateDomain
     * @throws InvalidDomainConfigurationException
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     */
    public function loadConfig(Domain $domain, $updateDomain = false) : void {

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

        $loadedDomain = $this->parse($loadedConfig);

        // Make sure, that the loaded domain has the same identifier as the given domain.
        if ($loadedDomain->getIdentifier() !== $domain->getIdentifier()) {
            throw new InvalidDomainConfigurationException(
                'The domain configuration identifier does not match with the filename.'
            );
        }

        // Override domain with content from loadedDomain.
        if($updateDomain) {
            $domain->setFromEntity($loadedDomain);
        }

        $domain->setConfig($loadedConfig);
    }

    /**
     * Removes the domain configuration JSON file for the given domain.
     *
     * @param Domain $domain
     * @throws MissingOrganizationException
     * @throws MissingDomainException
     * @throws IOException if the file cannot be deleted
     */
    public function removeConfig(Domain $domain) : void {

        $path = $this->getDomainConfigPath($domain);

        // If file does not exists, return.
        if(!$this->filesystem->exists($path)) {
            return;
        }

        $this->filesystem->remove($path);
    }

    /**
     * Get all config files without .json suffix (this is the domain identifier) for the given domain.
     *
     * @param Organization $organization
     * @return array
     * @throws MissingOrganizationException
     */
    public function listConfig(Organization $organization) : array {

        $path = $this->getOrganizationConfigPath($organization);

        if(!$this->filesystem->exists($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($path)->sortByName();
        $configs = [];

        foreach($finder as $file) {
            $configs[] = $file->getBasename('.json');
        }

        return $configs;
    }
}
