<?php


namespace UniteCMS\CoreBundle\Query;


use UniteCMS\CoreBundle\Content\ContentInterface;

class ReferenceDataFieldOrderBy extends BaseFieldOrderBy {

    protected $rootField = '';

    /**
     * @var string $entity
     */
    protected $entity;

    public function __construct(string $rootField, string $field, string $order, string $entity = ContentInterface::class)
    {
        $this->rootField = $rootField;
        $this->entity = $entity;
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

    /**
     * @return string
     */
    public function getEntity() : string
    {
        return $this->entity;
    }
}
