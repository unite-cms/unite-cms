<?php


namespace UniteCMS\CoreBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UniteCMS\CoreBundle\User\UserInterface;

interface TypeAwareUserProvider extends UserProviderInterface
{
    /**
     * Load a user by its username and a unite user type.
     *
     * @param string $username
     * @param string $type
     *
     * @throws UsernameNotFoundException if the user is not found
     * @return UserInterface
     */
    public function loadUserByUsernameAndType(string $username, string $type);
}
