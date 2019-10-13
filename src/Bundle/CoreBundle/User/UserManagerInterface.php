<?php


namespace UniteCMS\CoreBundle\User;

use UniteCMS\CoreBundle\Domain\Domain;

interface UserManagerInterface
{
    public function find(Domain $domain, string $type, string $username) : ?UserInterface;
}
