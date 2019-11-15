<?php


namespace UniteCMS\CoreBundle\Security\Authenticator;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator as BaseJWTTokenAuthenticator;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UniteCMS\CoreBundle\Security\User\TypeAwareUserProvider;

class JWTTokenAuthenticator extends BaseJWTTokenAuthenticator
{
    /**
     * {@inheritDoc}
     */
    protected function loadUser(UserProviderInterface $userProvider, array $payload, $identity)
    {
        if ($userProvider instanceof TypeAwareUserProvider) {
            return $userProvider->loadUserByUsernameAndType($identity, $payload['type']);
        }

        return $userProvider->loadUserByUsername($identity);
    }
}
