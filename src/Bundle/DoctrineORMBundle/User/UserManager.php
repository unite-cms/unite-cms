<?php


namespace UniteCMS\DoctrineORMBundle\User;

use Doctrine\Common\Persistence\ObjectManager;
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
     * @return ObjectManager
     */
    protected function em(Domain $domain) : ObjectManager {
        return $this->registry->getManager($domain->getId());
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

        $user = $this->repository($domain)->typedFindByUsername($type, $username);

        // TODO: Remove mock creation
        if(!$user) {
            $user = new User($type, $username);

            // = "password"
            $user->setPassword('$argon2id$v=19$m=65536,t=4,p=1$5tYQxe/wtmO5FNJuztFUWw$GL6B8OL/ovotqeF80ZKZSmUIHS55Xyk/EKjmvBjQyhU');
        }

        return $user;
    }
}
