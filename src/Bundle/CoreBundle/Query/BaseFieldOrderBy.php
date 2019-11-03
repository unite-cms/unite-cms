<?php


namespace UniteCMS\CoreBundle\Query;


class BaseFieldOrderBy
{
    /**
     * @var string $field
     */
    protected $field;

    /**
     * @var string $order
     */
    protected $order;

    public function __construct(string $field, string $order)
    {
        $this->field = $field;
        $this->order = $order;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }
}
