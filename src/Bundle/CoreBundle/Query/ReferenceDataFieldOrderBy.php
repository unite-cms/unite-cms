<?php


namespace UniteCMS\CoreBundle\Query;


class ReferenceDataFieldOrderBy extends BaseFieldOrderBy {

    protected $rootField = '';

    public function __construct(string $rootField, string $field, string $order)
    {
        $this->rootField = $rootField;
        parent::__construct($field, $order);
    }

    /**
     * {@inheritDoc}
     */
    public function getField() : string
    {
        return parent::getField() . '.data';
    }

    /**
     * @return string
     */
    public function getRootField() : string {
        return $this->rootField;
    }
}
