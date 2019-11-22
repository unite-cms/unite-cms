<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\BaseContentRevision;
use UniteCMS\CoreBundle\Content\FieldData;

/**
 * @ORM\Table(name="unite_revision")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\RevisionRepository")
 */
class Revision extends BaseContentRevision
{
    /**
     * @var string
     * @ORM\Id()
     * @ORM\GeneratedValue("UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $entityId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $entityType;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $operation;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $version;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $operationTime;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $operatorName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $operatorType;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $operatorId;

    /**
     * @var FieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $data;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
