<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use DateTime;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;

class TestContent implements ContentInterface
{
    protected $id;
    protected $type;
    protected $data;
    protected $deleted;

    public function __construct(string $type, array $data = [])
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function setId() : self {
        $this->id = uniqid();
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): string
    {
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

    /**
     * @param DateTime|null $deleted
     * @return $this
     */
    public function setDeleted(?DateTime $deleted = null) : self {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleted(): ?DateTime
    {
        return $this->deleted;
    }
}