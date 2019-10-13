<?php


namespace UniteCMS\CoreBundle\User;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

interface UserInterface extends BaseUserInterface, JWTUserInterface
{

}
