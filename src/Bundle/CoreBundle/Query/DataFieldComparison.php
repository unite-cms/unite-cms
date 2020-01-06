<?php


namespace UniteCMS\CoreBundle\Query;

class DataFieldComparison extends BaseFieldComparison {

    /**
     * @var array $path
     */
    protected $path;

    public function __construct($field, $operator, $value, array $path = ['data'])
    {
        $this->path = $path;
        parent::__construct($field, $operator, is_object($value) ? $value : new DataFieldValue($value));
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return join('.', array_merge([parent::getField()], $this->path));
    }

    /**
     * @return string
     */
    public function getRootField()
    {
        return parent::getField();
    }
}
