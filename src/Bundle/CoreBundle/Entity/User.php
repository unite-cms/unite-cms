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
 * @ORM\Entity()
 * @UniqueEntity(fields={"email"}, message="validation.email_already_taken")
 * @UniqueEntity(fields={"resetToken"}, message="validation.reset_token_present")
 */
class User implements UserInterface, \Serializable
{
    const PASSWORD_RESET_TTL = 14400; // Default to 4h
    const ROLE_USER = "ROLE_USER";
    const ROLE_PLATFORM_ADMIN = "ROLE_PLATFORM_ADMIN";
    /**
     * @var string
     * @Assert\Length(max="180", maxMessage="validation.too_long")
     * @Assert\Regex(pattern="/^[a-z0-9A-Z\-_]+$/i", message="validation.invalid_characters")
     * @ORM\Column(name="reset_token", type="string", length=180, unique=true, nullable=true)
     */
    protected $resetToken;
    /**
     * @var \DateTime
     * @ORM\Column(name="reset_requested_at", type="datetime", nullable=true)
     */
    protected $resetRequestedAt;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Email(message="validation.invalid_email")
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;
    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @ORM\Column(name="firstname", type="string", length=255)
     */
    private $firstname;
    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @ORM\Column(name="lastname", type="string", length=255)
     */
    private $lastname;
    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank", groups={"CREATE"})
     * @Assert\Length(max="255", maxMessage="validation.too_long")
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
     * @var OrganizationMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\OrganizationMember", mappedBy="user", cascade={"persist", "remove", "merge"})
     */
    private $organizations;
    /**
     * @var DomainMember[]
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UniteCMS\CoreBundle\Entity\DomainMember", mappedBy="user", cascade={"persist", "remove", "merge"})
     */
    private $domains;

    public function __construct()
    {
        $this->roles = [self::ROLE_USER];
        $this->domains = new ArrayCollection();
        $this->organizations = new ArrayCollection();
    }

    public function __toString()
    {
        return '' . $this->getFirstname() . ' ' . $this->getLastname();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

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
     * Returns the roles of the user for a given domain.
     *
     * @param Domain $domain
     * @return Role[]|string[] The user roles for the domain
     */
    public function getDomainRoles(Domain $domain)
    {
        foreach ($this->getDomains() as $domainMember) {
            if (!empty($domain->getId()) && $domainMember->getDomain()->getId() === $domain->getId()) {
                return $domainMember->getRoles();
            }
        }

        return [Domain::ROLE_PUBLIC];
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
            if ($organizationMember->getOrganization() === $organization) {
                return $organizationMember->getRoles();
            }
        }

        return [];
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
                $this->firstname,
                $this->lastname,
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
            $this->firstname,
            $this->lastname,
            $this->roles,
            ) = unserialize($serialized);
    }

    /**
     * @return OrganizationMember[]|ArrayCollection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * @param OrganizationMember[] $organizations
     *
     * @return User
     */
    public function setOrganizations($organizations)
    {
        $this->organizations->clear();
        foreach ($organizations as $organization) {
            $this->addOrganization($organization);
        }

        return $this;
    }

    /**
     * @param OrganizationMember $organization
     *
     * @return User
     */
    public function addOrganization(OrganizationMember $organization)
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations->add($organization);
            $organization->setUser($this);
        }

        return $this;
    }

    /**
     * @return DomainMember[]|ArrayCollection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param DomainMember[] $domains
     *
     * @return User
     */
    public function setDomains($domains)
    {
        $this->domains->clear();
        foreach ($domains as $domain) {
            $this->addDomain($domain);
        }

        return $this;
    }

    /**
     * @param DomainMember $domain
     *
     * @return User
     */
    public function addDomain(DomainMember $domain)
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);
            $domain->setUser($this);
        }

        return $this;
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

