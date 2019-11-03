<?php


namespace UniteCMS\CoreBundle\Query;


class DataFieldOrderBy extends BaseFieldOrderBy {

    /**
     * {@inheritDoc}
     */
    public function getField() : string
    {
        return parent::getField() . '.data';
    }
}
