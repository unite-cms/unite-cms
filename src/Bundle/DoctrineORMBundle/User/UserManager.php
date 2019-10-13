<?php


namespace UniteCMS\DoctrineORMBundle\User;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\User\UserInterface;
use UniteCMS\CoreBundle\User\UserManagerInterface;
use UniteCMS\DoctrineORMBundle\Entity\User;
use UniteCMS\DoctrineORMBundle\Repository\UserRepository;

class UserManager implements UserManagerInterface
{
    /**
     * @var \Symfony\Bridge\Doctrine\RegistryInterface
     */
    protected $registry;

    /**
     * ContentManager constructor.
     *
     * @param \Symfony\Bridge\Doctrine\RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return EntityManager
     */
    protected function em(Domain $domain) : EntityManager {
        return $this->registry->getEntityManager($domain->getId());
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     *
     * @return \UniteCMS\DoctrineORMBundle\Repository\UserRepository
     */
    protected function repository(Domain $domain) : UserRepository {
        return $this->em($domain)->getRepository(User::class);
    }

    public function find(Domain $domain, string $type, string $username): ?UserInterface {

        // TODO: Remove mock creation
        return $this->repository($domain)->typedFindByUsername($type, $username) ?? new User($type, $username);
    }
}
