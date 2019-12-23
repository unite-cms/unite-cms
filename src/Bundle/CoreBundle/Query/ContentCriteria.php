<?php

namespace UniteCMS\CoreBundle\Query;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

class ContentCriteria extends Criteria
{
    const BASE_FIELDS = ['id', 'created', 'updated', 'deleted', 'locale'];

    const NCONTAINS = 'NCONTAINS';

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
        'NCONTAINS' => self::NCONTAINS,
        'MEMBER_OF' => Comparison::MEMBER_OF,
        'STARTS_WITH' => Comparison::STARTS_WITH,
        'ENDS_WITH' => Comparison::ENDS_WITH,
    ];

    /**
     * @param $value
     * @param string|null $cast
     * @return mixed
     */
    static function castValue($value, ?string $cast = null) {
        switch ($cast) {
            case 'INT': return intval($value);
            case 'FLOAT': return floatval($value);
            case 'BOOLEAN': return boolval($value);
            default: return $value;
        }
    }

    /**
     * @var array
     */
    protected $orderBy = [];

    /**
     * @param BaseFieldOrderBy|BaseFieldOrderBy[] $orderings
     * @return ContentCriteria
     */
    public function orderBy($orderings) : ContentCriteria {
        $this->orderBy = is_array($orderings) ? $orderings : [$orderings];
        return $this;
    }

    /**
     * @return BaseFieldOrderBy[]
     */
    public function getOrderings() : array {
        return $this->orderBy;
    }
}
