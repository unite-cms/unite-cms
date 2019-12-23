<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\BaseContent;

/**
 * @ORM\Table(name="unite_content")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\ContentRepository")
 * @UniqueEntity({"locale", "translate"}, errorPath="locale", message="You cannot add two translations with the same locale.")
 */
class Content extends BaseContent
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
     * @var FieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $data = [];

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
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locale = null;

    /**
     * @var Content
     *
     * @ORM\ManyToOne(targetEntity="UniteCMS\DoctrineORMBundle\Entity\Content", inversedBy="translations")
     * @ORM\JoinColumn(name="translate_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $translate;

    /**
     * @var Content[]
     *
     * @ORM\OneToMany(targetEntity="UniteCMS\DoctrineORMBundle\Entity\Content", mappedBy="translate")
     */
    protected $translations;
}
