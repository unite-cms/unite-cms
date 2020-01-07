<?php


namespace UniteCMS\CoreBundle\Query;

use Doctrine\Common\Collections\Expr\Comparison;

class BaseFieldComparison extends Comparison {

    protected $customOperator = null;

    public function __construct($field, $operator, $value)
    {
        if(!empty(ContentCriteria::CUSTOM_OPERATOR_MAP[$operator])) {
            $this->customOperator = $operator;
            $operator = ContentCriteria::CUSTOM_OPERATOR_MAP[$operator];
        }

        parent::__construct($field, $operator, $value);
    }

    public function getCustomOperator() : ?string {
        return $this->customOperator;
    }
}
