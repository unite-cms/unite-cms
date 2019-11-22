<?php

namespace UniteCMS\CoreBundle\Content;

use DateTime;

abstract class BaseContentRevision implements ContentRevisionInterface
{
    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var string
     */
    protected $operation;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var DateTime
     */
    protected $operationTime;

    /**
     * @var string
     */
    protected $operatorName;

    /**
     * @var string
     */
    protected $operatorType;

    /**
     * @var string
     */
    protected $operatorId;

    /**
     * @var FieldData[]
     */
    protected $data;

    /**
     * BaseContentRevision constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->setOperationTime(new DateTime());
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
     * @return self
     */
    public function setEntityId(string $entityId): ContentRevisionInterface
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityType() : string
    {
        return $this->entityType;
    }

    /**
     * @param string $entityType
     * @return self
     */
    public function setEntityType(string $entityType): ContentRevisionInterface
    {
        $this->entityType = $entityType;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperation() : string
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     * @return self
     */
    public function setOperation($operation): ContentRevisionInterface
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return self
     */
    public function setVersion(int $version): ContentRevisionInterface
    {
        $this->version = $version;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperationTime() : DateTime
    {
        return $this->operationTime;
    }

    /**
     * @param DateTime $operationTime
     * @return self
     */
    public function setOperationTime($operationTime): ContentRevisionInterface
    {
        $this->operationTime = $operationTime;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperatorName() : string
    {
        return $this->operatorName;
    }

    /**
     * @param string $operatorName
     * @return self
     */
    public function setOperatorName($operatorName): ContentRevisionInterface
    {
        $this->operatorName = $operatorName;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperatorType() : ?string
    {
        return $this->operatorType;
    }

    /**
     * @param string|null $operatorType
     * @return self
     */
    public function setOperatorType(?string $operatorType = null): ContentRevisionInterface
    {
        $this->operatorType = $operatorType;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperatorId() : ?string
    {
        return $this->operatorId;
    }

    /**
     * @param string|null $operatorId
     * @return self
     */
    public function setOperatorId(?string $operatorId = null): ContentRevisionInterface
    {
        $this->operatorId = $operatorId;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param FieldData[] $data
     * @return self
     */
    public function setData(array $data): ContentRevisionInterface
    {
        $this->data = $data;
        return $this;
    }
}
