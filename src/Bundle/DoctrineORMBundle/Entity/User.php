<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Security\User\BaseUser;

/**
 * @ORM\Table(name="unite_user")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\UserRepository")
 * @UniqueEntity("username")
 * @UniqueEntity({"locale", "translate"}, errorPath="locale", message="You cannot add two translations with the same locale.")
 */
class User extends BaseUser
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
    protected $username = '';

    /**
     * @ORM\Column(type="string")
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
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updated;

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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locale = null;

    /**
     * @var Content
     *
     * @ORM\ManyToOne(targetEntity="UniteCMS\DoctrineORMBundle\Entity\Content", inversedBy="translations")
     */
    protected $translate;

    /**
     * @var Content[]
     *
     * @ORM\OneToMany(targetEntity="UniteCMS\DoctrineORMBundle\Entity\Content", mappedBy="translate")
     */
    protected $translations;
}
