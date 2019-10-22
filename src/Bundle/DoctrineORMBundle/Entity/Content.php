<?php

namespace UniteCMS\DoctrineORMBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\ContentInterface;

/**
 * @ORM\Table(name="unite_content")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\ContentRepository")
 */
class Content implements ContentInterface
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
    protected $data;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
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

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType() : string {
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
