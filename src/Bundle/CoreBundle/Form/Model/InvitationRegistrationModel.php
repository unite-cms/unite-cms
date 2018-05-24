<?php

namespace UniteCMS\CoreBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class InvitationRegistrationModel
{

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     */
    private $name;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Email(message="validation.invalid_email")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     */
    private $email;

    /**
     * @Assert\Length(min = 8, max="255", minMessage = "validation.too_short", maxMessage = "validation.too_long")
     */
    private $password;

    /**
     * Removes sensitive data from this object.
     */
    public function eraseCredentials()
    {
        $this->password = '';
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return InvitationRegistrationModel
     */
    public function setEmail(string $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return InvitationRegistrationModel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
