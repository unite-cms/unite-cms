<?php


namespace UniteCMS\CoreBundle\Security\User;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Domain\Domain;

interface UserManagerInterface extends ContentManagerInterface
{
    /**
     * Get a single user by his_hers username.
     *
     * @param Domain $domain
     * @param string $username
     *
     * @return ContentInterface|null
     */
    public function findByUsername(Domain $domain, string $username) : ?UserInterface;
}
