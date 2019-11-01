<?php

namespace UniteCMS\CoreBundle\Content;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use InvalidArgumentException;

class ContentCriteria extends Criteria
{
    protected $parameters = [];

    static function mapOperator(string $operator) {
        switch ($operator) {
            case 'EQ': return Comparison::EQ;
            case 'NEQ': return Comparison::NEQ;
            case 'LT': return Comparison::LT;
            case 'LTE': return Comparison::LTE;
            case 'GT': return Comparison::GT;
            case 'GTE': return Comparison::GTE;
            case 'IS': return Comparison::IS;
            case 'IN': return Comparison::IN;
            case 'NIN': return Comparison::NIN;
            case 'CONTAINS': return Comparison::CONTAINS;
            case 'MEMBER_OF': return Comparison::MEMBER_OF;
            case 'STARTS_WITH': return Comparison::STARTS_WITH;
            case 'ENDS_WITH': return Comparison::ENDS_WITH;
            default:
                throw new InvalidArgumentException();
        }
    }

    /**
     * @param array $where
     * @return \Doctrine\Common\Collections\Expr\Expression
     */
    protected function buildWhere(array $where) : Expression {

        if(!empty($where['AND'])) {
            return new CompositeExpression(
                CompositeExpression::TYPE_AND,
                array_map('self::buildWhere', $where['AND'])
            );
        }

        else if(!empty($where['OR'])) {
            return new CompositeExpression(
                CompositeExpression::TYPE_OR,
                array_map('self::buildWhere', $where['OR'])
            );
        }

        elseif(!empty($where['field']) && !empty($where['operator'])) {
            return new Comparison($where['field'], static::mapOperator($where['operator']), $where['value'] ?? new Value(null));
        }

        throw new InvalidArgumentException();
    }

    /**
     * @param array $contentOrderings
     * @return Criteria
     */
    public function contentOrderBy(array $contentOrderings = []) : Criteria {
        $orderings = [];

        foreach($contentOrderings as $ordering) {
            $orderings[$ordering['field']] = $ordering['order'];
        }

        return parent::orderBy(
            $orderings
        );
    }

    /**
     * @param array|null $where
     * @return \Doctrine\Common\Collections\Criteria
     */
    public function contentWhere(array $where = null) : Criteria {
        if(empty($where)) {
            return $this;
        }

        return $this->andWhere($this->buildWhere($where));
    }
}
