<?php

namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;

class TestUserManager extends TestContentManager implements UserManagerInterface {

    public function create(Domain $domain, string $type): ContentInterface {
        $content = new TestUser($type);
        $this->actions[] = function() use ($content) {
            $content->setId();
            $this->repository[$content->getType()][$content->getId()] = $content;
            $this->versionedData[$content->getId()] = $this->versionedData[$content->getId()] ?? [];
            $this->versionedData[$content->getId()][] = $content->getData();
        };
        return $content;
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
