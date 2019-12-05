<?php


namespace UniteCMS\CoreBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;

class DomainUserProvider implements UserProviderInterface
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
        $domain = $this->domainManager->current();
        if(!$user = $domain->getUserManager()->findByUsername($domain, $username)) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if(!$user instanceof \UniteCMS\CoreBundle\Security\User\UserInterface) {
            throw new UnsupportedUserException();
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return in_array(\UniteCMS\CoreBundle\Security\User\UserInterface::class, class_implements($class));
    }
}
