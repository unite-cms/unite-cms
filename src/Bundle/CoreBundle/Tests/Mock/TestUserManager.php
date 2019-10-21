<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;

class TestUserManager extends TestContentManager implements UserManagerInterface {

    public function findByUsername(
        Domain $domain,
        string $type,
        string $username
    ): ?UserInterface {
        // TODO: Implement findByUsername() method.
    }
}
