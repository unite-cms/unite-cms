<?php

namespace UniteCMS\CoreBundle\Content;

class FieldDataList extends FieldData {

    /**
     * @return \UniteCMS\CoreBundle\Content\FieldData[]
     */
    public function rows() : array {
        return $this->getData() ?? [];
    }
}
