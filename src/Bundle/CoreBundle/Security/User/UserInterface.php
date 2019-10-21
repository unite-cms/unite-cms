<?php


namespace UniteCMS\CoreBundle\Security\User;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;

interface UserInterface extends ContentInterface, BaseUserInterface, JWTUserInterface
{

}
