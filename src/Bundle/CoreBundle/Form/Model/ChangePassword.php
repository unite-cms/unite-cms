<?php

namespace UniteCMS\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePassword
{

    /**
     * @UserPassword(
     *     message = "validation.invalid_password",
     *     groups={"UPDATE"}
     * )
     */
    private $currentPassword;

    /**
     * @Assert\Length(min = 8, max="255", minMessage = "validation.too_short", maxMessage = "validation.too_long", groups={"UPDATE", "RESET"})
     */
    private $newPassword;

    /**
     * Removes sensitive data from this object.
     */
    public function eraseCredentials()
    {
        $this->currentPassword = '';
        $this->newPassword = '';
    }

    /**
     * @return mixed
     */
    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    /**
     * @param string $currentPassword
     *
     * @return $this
     */
    public function setCurrentPassword($currentPassword)
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNewPassword()
    {
        return $this->newPassword;
    }

    /**
     * @param string $newPassword
     *
     * @return $this
     */
    public function setNewPassword($newPassword)
    {
        $this->newPassword = $newPassword;

        return $this;
    }
}
