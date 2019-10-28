<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class TestUser extends TestContent implements UserInterface
{

    /**
     * Creates a new instance from a given JWT payload.
     *
     * @param string $username
     * @param array $payload
     *
     * @return JWTUserInterface
     */
    public static function createFromPayload($username, array $payload)
    {
        return new self($payload['type']);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {
        return ['ROLE_' . strtoupper($this->getType())];
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials(){}
}