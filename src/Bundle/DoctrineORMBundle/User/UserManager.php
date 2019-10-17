<?php


namespace UniteCMS\DoctrineORMBundle\User;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use UniteCMS\CoreBundle\Content\ContentInterface;
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

    /**
     * {@inheritDoc}
     */
    public function create(Domain $domain, string $type, array $inputData = [], bool $persist = false): ContentInterface {

        if(empty($inputData['username'])) {
            throw new AuthenticationCredentialsNotFoundException('Please provide an username input field!');
        }

        $user = new User($type, $inputData['username']);
        unset($inputData['username']);

        $user->setData($inputData);

        // TODO: How do we get in the password or any other auth strategy?
        // = "password"
        $user->setPassword('$argon2id$v=19$m=65536,t=4,p=1$5tYQxe/wtmO5FNJuztFUWw$GL6B8OL/ovotqeF80ZKZSmUIHS55Xyk/EKjmvBjQyhU');

        if($persist) {
            $this->em($domain)->persist($user);

            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($user);
        }

        return $user;
    }
}
