<?php


namespace UniteCMS\CoreBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;

interface UserInterface extends ContentInterface, BaseUserInterface
{
    public function setFullyAuthenticated(bool $fullyAuthenticated = true) : void;
    public function isFullyAuthenticated() : bool;

    public function getToken(string $key) : ?string;
    public function setToken(string $key, ?string $token = null) : void;
}
