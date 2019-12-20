<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Log\BaseLog;
use UniteCMS\CoreBundle\Log\LoggerInterface;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="unite_log")
 * @ORM\Entity
 */
class Log extends BaseLog
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
     * @ORM\Column(type="text")
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
}
