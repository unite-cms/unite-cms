<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\Security\User\UserInterface;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="unite_user")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\UserRepository")
 * @UniqueEntity("username")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue("UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    protected $type;

    /**
     * @var FieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $data = [];

    /**
     * @var SensitiveFieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $sensitiveData = [];

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    protected $username = '';

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $deleted = null;

    /**
     * @var array
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $tokens = [];

    /**
     * @var bool $fullyAuthenticated
     */
    protected $fullyAuthenticated = false;

    /**
     * Content constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType() : string {
        return $this->type;
    }

    /**
     * @return FieldData[]
     */
    public function getData(): array
    {
        if(!is_array($this->data)) {
            $this->data = [];
        }

        if(!is_array($this->sensitiveData)) {
            $this->sensitiveData = [];
        }

        return ($this->data + $this->sensitiveData + ['username' => new FieldData($this->getUsername())]);
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data) : self
    {
        $this->data = [];
        $this->sensitiveData = [];

        foreach($data as $name => $value) {
            if($name === 'username') {
                $this->username = $value;
            }

            else if ($value instanceof SensitiveFieldData) {
                $this->sensitiveData[$name] = $value;
            }

            else {
                $this->data[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * @param string $fieldName
     * @return FieldData|null
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return $fieldName === 'username' ?
            new FieldData($this->getUsername()) :
            isset($this->getData()[$fieldName]) ? $this->getData()[$fieldName] : null;
    }

    public function getUsername() : string {
        return $this->username;
    }

    /**
     * @param DateTime|null $deleted
     * @return $this
     */
    public function setDeleted(?DateTime $deleted = null) : self {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleted(): ?DateTime
    {
        return $this->deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {
        return [sprintf('ROLE_%s', strtoupper($this->getType()))];
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials() {}

    /**
     * {@inheritDoc}
     */
    public function setFullyAuthenticated(bool $fullyAuthenticated = true) : void {
        $this->fullyAuthenticated = $fullyAuthenticated;
    }

    /**
     * {@inheritDoc}
     */
    public function isFullyAuthenticated() : bool {
        return $this->fullyAuthenticated;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(string $key) : ?string {
        return $this->tokens[$key] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function setToken(string $key, ?string $token = null) : void {
        $this->tokens[$key] = $token;
    }
}
