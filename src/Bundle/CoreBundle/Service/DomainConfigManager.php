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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\InvalidDomainConfigurationException;
use UniteCMS\CoreBundle\Exception\InvalidOrganizationConfigurationException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Event\DomainConfigFileEvent;

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

    /**
     * @var EventDispatcherInterface $dispatcher
     */
    private $dispatcher;

    public function __construct(string $domainConfigDir, DomainDefinitionParser $definitionParser, Filesystem $filesystem, EventDispatcherInterface $dispatcher)
    {
        $this->domainConfigDir = $domainConfigDir;
        $this->domainDefinitionParser = $definitionParser;
        $this->filesystem = $filesystem;
        $this->dispatcher = $dispatcher;
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

        $identifier = $organization->getIdentifier() ?? '';
        if(preg_match('/[^a-z0-9_]+/', $identifier)) {
            throw new MissingOrganizationException('Organization identifier contains invalid characters.');
        }


        if(empty($identifier)) {
            throw new MissingOrganizationException('Organization identifier is empty.');
        }

        return $this->domainConfigDir . $identifier . '/';
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

        $identifier = $domain->getIdentifier() ?? '';
        if(preg_match('/[^a-z0-9_]+/', $identifier)) {
            throw new MissingDomainException('Domain identifier contains invalid characters.');
        }

        if(empty($identifier)) {
            throw new MissingDomainException('You can only process domains where the identifier is not empty.');
        }

        return $this->getOrganizationConfigPath($domain->getOrganization()) . $identifier . '.json';
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
     * @param string $previous_identifier
     * @param bool $forceOverride , If provided, an existing domain config file will be overridden.
     *
     * @return bool, Returns true if domain was dumped successfully. False if file did exist and forceOverride was set to false.
     *
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     * @throws IOException if the file cannot be written
     * @throws InvalidDomainConfigurationException
     */
    public function updateConfig(Domain $domain, string $previous_identifier = null, bool $forceOverride = false) : bool{

        $this->checkConfigFolder();

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

        // If old config file exists, remove it.
        if($previous_identifier) {
            $previous_organization = new Organization();
            $previous_organization->setIdentifier($domain->getOrganization()->getIdentifier());
            $previous_domain = new Domain();
            $previous_domain->setIdentifier($previous_identifier)->setOrganization($previous_organization);
            $this->removeConfig($previous_domain);
            unset($previous_domain);
            unset($previous_organization);
        }

        // dispatch domain config create/update events
        if ($this->filesystem->exists($path)) {
            // update existing file
            $this->dispatcher->dispatch(DomainConfigFileEvent::DOMAIN_CONFIG_FILE_UPDATE, new DomainConfigFileEvent($domain));
        }
        else {
            // create fiel
            $this->dispatcher->dispatch(DomainConfigFileEvent::DOMAIN_CONFIG_FILE_CREATE, new DomainConfigFileEvent($domain));
        }

        $this->filesystem->dumpFile($path, json_encode(json_decode($config), JSON_PRETTY_PRINT));
        return true;
    }

    /**
     * Creates a new folder for an organization. If the folder already exists, an exception will be thrown.
     *
     * @param Organization $organization
     * @throws MissingOrganizationException
     * @throws InvalidOrganizationConfigurationException
     */
    public function createOrganizationFolder(Organization $organization) {

        $this->checkConfigFolder();
        $path = $this->getOrganizationConfigPath($organization);

        if($this->filesystem->exists($path)) {
            throw new InvalidOrganizationConfigurationException('An organization folder with this identifier already exists! Please delete the folder first, to create an organization with this identifier.');
        }

        $this->filesystem->mkdir($path);
    }

    /**
     * Renames an organization config folder or creates one, if old folder is missing.
     *
     * @param Organization $organization
     * @param string $previous_identifier
     * @throws InvalidOrganizationConfigurationException
     * @throws MissingOrganizationException
     */
    public function renameOrganizationFolder(Organization $organization, string $previous_identifier) {

        $this->checkConfigFolder();
        $path = $this->getOrganizationConfigPath($organization);

        $previous_organization = new Organization();
        $previous_organization->setIdentifier($previous_identifier);
        $previous_path = $this->getOrganizationConfigPath($previous_organization);
        unset($previous_organization);

        if($this->filesystem->exists($path)) {
            throw new InvalidOrganizationConfigurationException('An organization folder with this identifier already exists!');
        }

        if($this->filesystem->exists($previous_path)) {
            $this->filesystem->rename($previous_path, $path);
        } else {
            $this->filesystem->mkdir($path);
        }

    }

    /**
     * Removes an organization config folder.
     *
     * @param Organization $organization
     * @throws MissingOrganizationException
     */
    public function removeOrganizationFolder(Organization $organization) {

        $this->checkConfigFolder();
        $path = $this->getOrganizationConfigPath($organization);

        // If folder does not exists, return.
        if(!$this->filesystem->exists($path)) {
            return;
        }

        $this->filesystem->remove($path);
    }

    /**
     * Tries to create the config folder
     */
    public function checkConfigFolder() : void {

        $config_path = $this->getDomainConfigDir();

        // if domain config folder was never created
        if (!$this->filesystem->exists($config_path)) {

            // try to create the domain config folder, will work only systems with the appropriate on the parent folder
            // throws an IOException on fail
            $this->filesystem->mkdir($config_path);

        }

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
        $this->checkConfigFolder();
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

        $this->checkConfigFolder();

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
                sprintf('The domain configuration identifier "%s" does not match with the filename "%s".', $loadedDomain->getIdentifier(), $domain->getIdentifier().'.json')
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

        $this->checkConfigFolder();

        $path = $this->getDomainConfigPath($domain);

        // If file does not exists, return.
        if(!$this->filesystem->exists($path)) {
            return;
        }

        $this->filesystem->remove($path);

        // dispatch delete event
        $this->dispatcher->dispatch(DomainConfigFileEvent::DOMAIN_CONFIG_FILE_DELETE, new DomainConfigFileEvent($domain));
    }

    /**
     * Removes all domain configuration Files from Folder
     *
     * @throws IOException if the folder cannot be deleted or created
     */
    public function removeAllConfig() : void {

        $path = $this->getDomainConfigDir();

        // remove whole folder
        $this->filesystem->remove($path);

        // recreate empty folder
        $this->checkConfigFolder();
    }

    /**
     * Get all config files without .json suffix (this is the domain identifier) for the given domain.
     *
     * @param Organization $organization
     * @return array
     * @throws MissingOrganizationException
     */
    public function listConfig(Organization $organization) : array {

        $this->checkConfigFolder();

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
