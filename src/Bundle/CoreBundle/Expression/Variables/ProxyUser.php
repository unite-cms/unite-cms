<?php


namespace UniteCMS\CoreBundle\Expression\Variables;

use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface as UniteUserInterface;

class ProxyUser extends ProxyContent
{
    protected $user = null;

    public function __construct(?UserInterface $user = null) {

        if(!$user) {
            return;
        }

        $this->user = $user;

        if($user instanceof UniteUserInterface) {
            parent::__construct($user);
        }
    }

    /**
     * Returns true if at least one of the given roles is in object roles.
     * @param string|array $roles
     * @return bool
     */
    public function hasRole($roles) : bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach($roles as $role) {
            if(in_array($role, $this->getRoles())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getRoles() : array
    {
        if(!$this->user) {
            return [];
        }

        return $this->user->getRoles();
    }

    /**
     * @return mixed
     */
    public function getUsername() : string
    {
        if(!$this->user) {
            return [];
        }

        return $this->user->getUsername();
    }

    /**
     * @return bool
     */
    public function isAnonymous() : bool {
        return empty($this->user);
    }

    /**
     * @return bool
     */
    public function isFullyAuthenticated() : bool {
        if($this->isAnonymous()) {
           return false;
        }

        if(!method_exists($this->user, 'isFullyAuthenticated')) {
            return false;
        }

        return $this->user->isFullyAuthenticated();
    }
}
