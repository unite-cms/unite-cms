<?php


namespace UniteCMS\CoreBundle\Content\Embedded;

use DateTime;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;

class EmbeddedContent implements ContentInterface
{

    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var array $data
     */
    protected $data;

    public function __construct(string $id, string $type, array $data = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
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

    public function getFieldData(string $fieldName): ?FieldData
    {
        return isset($this->data[$fieldName]) ? $this->data[$fieldName] : null;
    }

    // Will always return null
    public function getDeleted(): ?DateTime
    {
        return null;
    }
}
