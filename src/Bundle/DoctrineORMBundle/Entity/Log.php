<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Log\LogInterface;
use UniteCMS\CoreBundle\Log\LoggerInterface;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="unite_log")
 * @ORM\Entity
 */
class Log implements LogInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue("UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=LoggerInterface::LEVELS)
     * @Assert\NotBlank()
     */
    protected $level;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    protected $message;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $username = null;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getLevel() : string
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     * @return Log
     */
    public function setLevel($level): self
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessage() : string
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     * @return Log
     */
    public function setMessage($message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername() : ?string
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return Log
     */
    public function setUsername($username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * @return Log
     */
    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }
}
