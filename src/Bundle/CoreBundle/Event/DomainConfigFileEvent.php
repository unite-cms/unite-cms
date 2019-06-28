<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 12.12.18
 * Time: 10:30
 */

namespace UniteCMS\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use UniteCMS\CoreBundle\Entity\Domain;

class DomainConfigFileEvent extends Event
{
    const DOMAIN_CONFIG_FILE_CREATE = 'unite.domain_config_file.create';
    const DOMAIN_CONFIG_FILE_UPDATE = 'unite.domain_config_file.update';
    const DOMAIN_CONFIG_FILE_DELETE = 'unite.domain_config_file.delete';

    /**
     * @var Donain $domain
     */
    private $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return Domain
     */
    public function getDomain() : Domain
    {
        return $this->domain;
    }
}