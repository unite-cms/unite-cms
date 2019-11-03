<?php


namespace UniteCMS\CoreBundle\Query;

class DataFieldComparison extends BaseFieldComparison {

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return parent::getField() . '.data';
    }
}
