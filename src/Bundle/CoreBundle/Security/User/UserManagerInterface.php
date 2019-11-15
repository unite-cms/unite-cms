<?php


namespace UniteCMS\CoreBundle\Security\User;

use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Domain\Domain;

interface UserManagerInterface extends ContentManagerInterface
{
    public function findByUsername(Domain $domain, string $type, string $username) : ?UserInterface;
}
