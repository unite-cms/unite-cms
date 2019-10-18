<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\User\UserInterface;

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
    protected $data;

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
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data) : self
    {
        if(isset($data['username'])) {
            $this->username = $data['username'];
            unset($data['username']);
        }

        $this->data = $data;
        return $this;
    }

    /**
     * @param string $fieldName
     *
     * @return \UniteCMS\CoreBundle\Content\FieldData|null
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return isset($this->data[$fieldName]) ? $this->data[$fieldName] : null;
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
