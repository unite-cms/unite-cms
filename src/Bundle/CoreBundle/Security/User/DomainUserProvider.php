<?php


namespace UniteCMS\CoreBundle\Security\User;

use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Security\User\TypeAwareUserProvider;

class DomainUserProvider implements TypeAwareUserProvider
{

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        throw new InvalidArgumentException('Please call loadUserByUsernameAndType() with a user name and unite cms user type.');
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsernameAndType(string $username, string $type)
    {
        $domain = $this->domainManager->current();
        if(!$user = $domain->getUserManager()->findByUsername($domain, $type, $username)) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if(!$user instanceof \UniteCMS\CoreBundle\User\UserInterface) {
            throw new UnsupportedUserException();
        }

        return $this->loadUserByUsernameAndType($user->getUsername(), $user->getType());
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return in_array(\UniteCMS\CoreBundle\User\UserInterface::class, class_implements($class));
    }
}
