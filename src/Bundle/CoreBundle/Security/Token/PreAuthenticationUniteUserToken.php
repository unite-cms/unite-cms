<?php


namespace UniteCMS\CoreBundle\Security\Token;

use Symfony\Component\Security\Guard\Token\PreAuthenticationGuardToken;

class PreAuthenticationUniteUserToken extends PreAuthenticationGuardToken
{
    /**
     * @var string $username
     */
    protected $username;

    /**
     * @var string $type
     */
    protected $type;

    public function __construct(string $username, string $credentials, string $type)
    {
        parent::__construct($credentials, '');
        $this->username = $username;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getUsername() : string {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getType() : string {
        return $this->type;
    }
}
