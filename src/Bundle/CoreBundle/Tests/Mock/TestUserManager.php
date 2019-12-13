<?php

namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;

class TestUserManager extends TestContentManager implements UserManagerInterface {

    public function create(Domain $domain, string $type): ContentInterface {
        return new TestUser($type);
    }

    public function findByUsername(Domain $domain, string $username): ?UserInterface {
        foreach($this->repository as $repository) {
            foreach($repository as $content) {
                if($content instanceof UserInterface && $content->getUsername() === $username) {
                    return $content;
                }
            }
        }

        return null;
    }
}
