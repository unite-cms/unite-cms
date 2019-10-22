<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;

/**
 * @ORM\Table(name="unite_revision")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\RevisionRepository")
 */
class Revision
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
     * @ORM\Column(type="string")
     */
    protected $operatorName;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $operatorType;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $operatorId;

    /**
     * @var FieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $data;

    public function __construct()
    {
        $this->setOperationTime(new DateTime());
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param mixed $entityType
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setEntityType($entityType): self
    {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param mixed $operation
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setOperation($operation): self
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperationTime()
    {
        return $this->operationTime;
    }

    /**
     * @param mixed $operationTime
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setOperationTime($operationTime): self
    {
        $this->operationTime = $operationTime;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperatorName()
    {
        return $this->operatorName;
    }

    /**
     * @param mixed $operatorName
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setOperatorName($operatorName): self
    {
        $this->operatorName = $operatorName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperatorType()
    {
        return $this->operatorType;
    }

    /**
     * @param mixed $operatorType
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setOperatorType($operatorType): self
    {
        $this->operatorType = $operatorType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOperatorId()
    {
        return $this->operatorId;
    }

    /**
     * @param mixed $operatorId
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setOperatorId($operatorId): self
    {
        $this->operatorId = $operatorId;
        return $this;
    }

    /**
     * @return \UniteCMS\CoreBundle\Content\FieldData[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param \UniteCMS\CoreBundle\Content\FieldData[] $data
     *
     * @return \UniteCMS\DoctrineORMBundle\Entity\Revision
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
}
