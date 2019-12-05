<?php


namespace UniteCMS\DoctrineORMBundle\User;

use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserManagerInterface;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Entity\User;

class UserManager extends ContentManager implements UserManagerInterface
{
    const ENTITY = User::class;

    /**
     * {@inheritDoc}
     */
    public function findByUsername(Domain $domain, string $username): ?UserInterface {
        return $this->repository($domain)->findByUsername($username);
    }
}
