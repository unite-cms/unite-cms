<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\ContentFieldInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\User\UserInterface;

/**
 * @ORM\Table(name="unite_user")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\UserRepository")
 */
class User implements ContentInterface, UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue("UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     */
    protected $username = '';

    /**
     * @ORM\Column(type="string")
     */
    protected $password = '';

    /**
     * Content constructor.
     *
     * @param string $type
     * @param string $username
     */
    public function __construct(string $type, string $username)
    {
        $this->type = $type;
        $this->username = $username;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType() : string {
        return $this->type;
    }

    /**
     * @return ContentFieldInterface[]
     */
    public function getData(): array
    {
        return [];
    }

    public function getFieldData(string $fieldName): ?ContentFieldInterface
    {
        return null;
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
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return $this->password;
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
        dump($username);
        dump($payload);
    }
}
