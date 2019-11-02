<?php


namespace UniteCMS\CoreBundle\Query;


class QueryOrderBy
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
     * @param string $alias
     *
     * @return string
     */
    public function getField(string $alias): string
    {
        return sprintf('%s.%s', $alias, $this->field);
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }
}
