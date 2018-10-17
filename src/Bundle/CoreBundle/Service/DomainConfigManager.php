<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 16.10.18
 * Time: 12:16
 */

namespace UniteCMS\CoreBundle\Service;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;

class DomainConfigManager
{
    /**
     * Dumps the domain as a JSON file to the organization directory in the defined domain config location.
     *
     * @param Domain $domain
     * @param bool $forceOverride, If provided, an existing domain config file will be overridden.
     */
    public function dumpDomainConfig(Domain $domain, bool $forceOverride) : void {
        // TODO
    }

    /**
     * Reads the domain configuration JSON file and updates the given domain to it.
     *
     * @param Domain $domain
     */
    public function updateDomainFromConfig(Domain $domain) : void {
        // TODO
    }
}
