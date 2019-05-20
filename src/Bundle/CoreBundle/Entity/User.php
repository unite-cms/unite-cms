<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 *
 * @ORM\Table(name="unite_user")
 * @ORM\Entity
 * @UniqueEntity(fields={"email"}, message="email_already_member")
 * @UniqueEntity(fields={"resetToken"}, message="reset_token_present")
 */
class User extends DomainAccessor implements UserInterface, \Serializable
{
    static function getType() : string { return 'user'; }

    const PASSWORD_RESET_TTL = 14400; // Default to 4h
    const ROLE_USER = "ROLE_USER";
    const ROLE_PLATFORM_ADMIN = "ROLE_PLATFORM_ADMIN";

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @Assert\Email(message="invalid_email")
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank", groups={"CREATE"})
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;

    /**
     * @var string
     * @Assert\Length(max="180", maxMessage="too_long")
     * @Assert\Regex(pattern="/^[a-z0-9A-Z\-_]+$/", message="invalid_characters")
     * @ORM\Column(name="reset_token", type="string", length=180, unique=true, nullable=true)
     */
    protected $resetToken;

    /**
     * @var \DateTime
     * @ORM\Column(name="reset_requested_at", type="datetime", nullable=true)
     */
    protected $resetRequestedAt;

    /**
     * @var OrganizationMember[]
     * @Assert\Valid(groups={"Default", "CREATE", "UPDATE", "DELETE"})
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\OrganizationMember", mappedBy="user", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    protected $organizations;

    public function __construct()
    {
        parent::__construct();
        $this->organizations = new ArrayCollection();
        $this->roles = [self::ROLE_USER];
    }

    public function __toString()
    {
        return ''.$this->getName();
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return User
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

    /**
     * @param array $roles
     *
     * @return User
     */
    public function setRoles(array $roles)
    {

        $user_role_found = false;

        foreach ($roles as $role) {
            if ($role instanceof Role && $role->getRole() == self::ROLE_USER) {
                $user_role_found = true;
            }
            if (is_string($role) && $role == self::ROLE_USER) {
                $user_role_found = true;
            }
        }

        $this->roles = $roles;

        if (!$user_role_found) {
            array_unshift($this->roles, self::ROLE_USER);
        }

        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return Role[]|string[] The user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getEmail();
    }

    /**
     * @return OrganizationMember[]|ArrayCollection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * @param OrganizationMember[] $oMembers
     *
     * @return User
     */
    public function setOrganizations($oMembers)
    {
        $this->organizations->clear();
        foreach ($oMembers as $oMember) {
            $this->addOrganization($oMember);
        }

        return $this;
    }

    /**
     * @param OrganizationMember $oMember
     *
     * @return User
     */
    public function addOrganization(OrganizationMember $oMember)
    {
        if (!$this->organizations->contains($oMember)) {
            $this->organizations->add($oMember);
            $oMember->setUser($this);
        }

        return $this;
    }

    /**
     * Returns all organizations, this accessor has access to.
     *
     * @return Organization[]
     */
    public function getAccessibleOrganizations(): array
    {
        $organizations = [];
        foreach($this->getOrganizations() as $organizationMember) {
            $organizations[] = $organizationMember->getOrganization();
        }
        return $organizations;
    }

    /**
     * Returns the roles of the user for a given organization.
     *
     * @param Organization $organization
     * @return Role[]|string[] The user roles for the organization
     */
    public function getOrganizationRoles(Organization $organization)
    {
        foreach ($this->getOrganizations() as $organizationMember) {
            if (!empty($organization->getId()) && $organizationMember->getOrganization()->getId() === $organization->getId()) {
                return $organizationMember->getRoles();
            }
        }

        return [];
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->id,
                $this->email,
                $this->password,
                $this->name,
                $this->roles,
            )
        );
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            $this->name,
            $this->roles,
            ) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function getResetToken()
    {
        return $this->resetToken;
    }

    /**
     * @param string $resetToken
     *
     * @return  User
     */
    public function setResetToken(string $resetToken)
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getResetRequestedAt()
    {
        return $this->resetRequestedAt;
    }

    /**
     * @param \DateTime $resetRequestedAt
     *
     * @return User
     */
    public function setResetRequestedAt(\DateTime $resetRequestedAt)
    {
        $this->resetRequestedAt = \DateTime::createFromFormat('d/m/Y H:i:s', $resetRequestedAt->format('d/m/Y H:i:s'));

        return $this;
    }

    /**
     * Clears the current reset token and resets the requestedAt value.
     *
     * @return User
     */
    public function clearResetToken()
    {
        $this->resetToken = null;
        $this->resetRequestedAt = null;

        return $this;
    }

    /**
     * Returns true, if the reset request is expired.
     *
     * @param \DateTime $now , override the actual datetime
     * @param int $ttl , override the default ttl
     * @return bool
     */
    public function isResetRequestExpired($now = null, $ttl = null)
    {

        if (!$this->getResetRequestedAt()) {
            return true;
        }

        $now = $now ?? new \DateTime();
        $ttl = $ttl ?? self::PASSWORD_RESET_TTL;

        return ($this->getResetRequestedAt()->getTimestamp() + $ttl) <= $now->getTimestamp();
    }
}

