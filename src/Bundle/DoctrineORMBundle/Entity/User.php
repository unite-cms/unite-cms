<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\Security\User\UserInterface;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="unite_user")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\UserRepository")
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
     * @Assert\Unique()
     */
    protected $username = '';

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

        return ($this->data + $this->sensitiveData);
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
     *
     * @return \UniteCMS\CoreBundle\Content\FieldData|null
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return isset($this->getData()[$fieldName]) ? $this->getData()[$fieldName] : null;
    }

    public function getUsername() : string {
        return $this->username;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {
        return [sprintf('ROLE_%s', strtoupper($this->getType()))];
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password) : self {
        $this->password = $password;
        return $this;
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
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * {@inheritDoc}
     */
    public static function createFromPayload($username, array $payload)
    {
        // TODO: Implement eraseCredentials() method.
    }
}
