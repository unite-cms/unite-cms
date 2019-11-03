<?php

namespace UniteCMS\CoreBundle\Query;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class ContentCriteria extends Criteria
{
    const BASE_FIELDS = ['id'];

    const OPERATOR_MAP = [
        'EQ' => Comparison::EQ,
        'NEQ' => Comparison::NEQ,
        'LT' => Comparison::LT,
        'LTE' => Comparison::LTE,
        'GT' => Comparison::GT,
        'GTE' => Comparison::GTE,
        'IS' => Comparison::IS,
        'IN' => Comparison::IN,
        'NIN' => Comparison::NIN,
        'CONTAINS' => Comparison::CONTAINS,
        'MEMBER_OF' => Comparison::MEMBER_OF,
        'STARTS_WITH' => Comparison::STARTS_WITH,
        'ENDS_WITH' => Comparison::ENDS_WITH,
    ];

    /**
     * @var array
     */
    protected $orderBy = [];

    /**
     * @param BaseFieldOrderBy[] $orderings
     * @return ContentCriteria
     */
    public function orderBy(array $orderings) : ContentCriteria {
        $this->orderBy = $orderings;
        return $this;
    }

    /**
     * @return BaseFieldOrderBy[]
     */
    public function getOrderings() : array {
        return $this->orderBy;
    }
}
