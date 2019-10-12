<?php

namespace UniteCMS\CoreBundle\Domain;

use LogicException;

class DomainManager
{
    /**
     * @var array
     */
    protected $domainConfig = [];

    /**
     * @var Domain
     */
    protected $domain = null;

    public function __construct(array $domainConfig = [])
    {
        $this->domainConfig = $domainConfig;
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
            $config['schema']
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
}
