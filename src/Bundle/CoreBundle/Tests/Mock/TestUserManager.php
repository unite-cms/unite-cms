<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\User\UserInterface;
use UniteCMS\CoreBundle\User\UserManagerInterface;

class TestUserManager implements UserManagerInterface
{
    public function find(
        Domain $domain,
        string $type,
        string $username
    ): ?UserInterface {
        // TODO: Implement find() method.
    }
}
