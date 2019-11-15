<?php


namespace UniteCMS\CoreBundle\Content\Embedded;

use UniteCMS\CoreBundle\Content\FieldData;

class EmbeddedFieldData extends FieldData
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }

    public function __construct(string $id, string $type, array $data = [])
    {
        $this->id = $id;
        $this->type = $type;
        parent::__construct($data);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
