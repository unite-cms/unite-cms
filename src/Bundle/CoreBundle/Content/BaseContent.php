<?php


namespace UniteCMS\CoreBundle\Content;

use DateTime;

/**
 * A base content class that implements ContentInterface.
 *
 * @package UniteCMS\CoreBundle\Content
 */
abstract class BaseContent implements ContentInterface
{

    /**
     * @var string|null
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var FieldData[]
     */
    protected $data = [];

    /**
     * @var null|DateTime
     */
    protected $deleted = null;

    /**
     * Content constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isNew() : bool {
        return empty($this->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getType() : string {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        if(!is_array($this->data)) {
            $this->data = [];
        }

        return $this->data;
    }

    /**
     * @param FieldData[] $data
     * @return self
     */
    public function setData(array $data) : ContentInterface
    {
        $this->data = $data;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return isset($this->data[$fieldName]) ? $this->data[$fieldName] : null;
    }

    /**
     * @param DateTime|null $deleted
     * @return self
     */
    public function setDeleted(?DateTime $deleted = null) : ContentInterface {
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
