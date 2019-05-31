<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * APIKey
 *
 * @ORM\Table(name="api_key")
 * @ORM\Entity(repositoryClass="UniteCMS\CoreBundle\Repository\ApiKeyRepository")
 * @UniqueEntity(fields={"token", "organization"}, message="token_present")
 * @UniqueEntity(fields={"name", "organization"}, message="name_present")
 */
class ApiKey extends DomainAccessor implements UserInterface, \Serializable
{
    static function getType() : string { return 'api_key'; }
    static function getNameField() : string { return 'name'; }

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="180", maxMessage="too_long")
     * @Assert\Regex(pattern="/^[a-z0-9A-Z\-_]+$/", message="invalid_characters")
     * @ORM\Column(name="token", type="string", length=180, nullable=true)
     */
    protected $token;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @ORM\Column(name="origin", type="string", length=255)
     */
    private $origin = '*';

    /**
     * @var Organization
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Organization", inversedBy="apiKeys")
     */
    private $organization;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    public function __toString()
    {
        return ''.$this->getName();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ApiKey
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
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return ApiKey
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
        $organization->addApiKey($this);

        return $this;
    }

    /**
     * Returns all organizations, this accessor has access to.
     *
     * @return Organization[]
     */
    public function getAccessibleOrganizations(): array
    {
        return $this->getOrganization() ? [$this->getOrganization()] : [];
    }

    /**
     * API Keys always returns ROLE_USER.
     *
     * @return array
     */
    public function getRoles()
    {
        return [ User::ROLE_USER ];
    }

    /**
     * API Keys do not have a password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * API Keys do not have a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the API Key name.
     *
     * @return string The API Key name.
     */
    public function getUsername()
    {
        return $this->getName();
    }

    /**
     * Removes sensitive data from the API Key.
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
                $this->created,
                $this->name,
                $this->token,
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
            $this->created,
            $this->name,
            $this->token,
            ) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return ApiKey
     */
    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param string $origin
     *
     * @return ApiKey
     */
    public function setOrigin(string $origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
}

