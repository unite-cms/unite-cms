<?php


namespace UniteCMS\DoctrineORMBundle\User;

use Symfony\Bridge\Doctrine\RegistryInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\User\UserInterface;
use UniteCMS\CoreBundle\User\UserManagerInterface;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Entity\User;

class UserManager extends ContentManager implements UserManagerInterface
{
    const ENTITY = User::class;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry);
    }

    /**
     * {@inheritDoc}
     */
    public function findByUsername(Domain $domain, string $type, string $username): ?UserInterface {
        return $this->repository($domain)->typedFindByUsername($type, $username);
    }
}
