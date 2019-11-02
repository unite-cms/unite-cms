<?php

namespace UniteCMS\CoreBundle\Query;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use InvalidArgumentException;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class QueryCriteria extends Criteria
{
    /**
     * @var QueryOrderBy[]
     */
    protected $queryOrderByStatements = [];

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
     * @param \UniteCMS\CoreBundle\Field\FieldTypeManager $fieldTypeManager
     * @param \UniteCMS\CoreBundle\ContentType\ContentType $contentType
     * @param array $where
     *
     * @return \Doctrine\Common\Collections\Expr\Expression
     */
    protected function buildWhere(FieldTypeManager $fieldTypeManager, ContentType $contentType, array $where) : Expression {

        if(!empty($where['AND'])) {
            return new CompositeExpression(
                CompositeExpression::TYPE_AND,
                array_map(function($and_where) use($fieldTypeManager, $contentType) {
                    return $this->buildWhere($fieldTypeManager, $contentType, $and_where);
                }, $where['AND'])
            );
        }

        else if(!empty($where['OR'])) {
            return new CompositeExpression(
                CompositeExpression::TYPE_OR,
                array_map(function($or_where) use($fieldTypeManager, $contentType) {
                    return $this->buildWhere($fieldTypeManager, $contentType, $or_where);
                }, $where['OR'])
            );
        }

        elseif(!empty($where['field']) && !empty($where['operator'])) {

            if($field = $contentType->getField($where['field'])) {
                $fieldType = $fieldTypeManager->getFieldType($field->getType());
                if($comparison = $fieldType->queryComparison($field, $where)) {
                    return $comparison;
                }
            }
        }

        throw new InvalidArgumentException();
    }

    /**
     * @param \UniteCMS\CoreBundle\Field\FieldTypeManager $fieldTypeManager
     * @param \UniteCMS\CoreBundle\ContentType\ContentType $contentType
     * @param array $contentOrderings
     *
     * @return Criteria
     */
    public function contentOrderBy(FieldTypeManager $fieldTypeManager, ContentType $contentType, array $contentOrderings = []) : Criteria {

        $this->queryOrderByStatements = [];

        foreach($contentOrderings as $ordering) {
            if($field = $contentType->getField($ordering['field'])) {
                $fieldType = $fieldTypeManager->getFieldType($field->getType());
                if($sortBy = $fieldType->queryOrderBy($field, $ordering)) {
                    $this->queryOrderByStatements[] = $sortBy;
                }
            }
        }
        return $this;
    }

    /**
     * @param \UniteCMS\CoreBundle\Field\FieldTypeManager $fieldTypeManager
     * @param \UniteCMS\CoreBundle\ContentType\ContentType $contentType
     * @param array|null $where
     *
     * @return \Doctrine\Common\Collections\Criteria
     */
    public function contentWhere(FieldTypeManager $fieldTypeManager, ContentType $contentType, array $where = null) : Criteria {
        if(empty($where)) {
            return $this;
        }

        return $this->andWhere($this->buildWhere($fieldTypeManager, $contentType, $where));
    }

    /**
     * @return \UniteCMS\CoreBundle\Query\QueryOrderBy[]
     */
    public function getQueryOrderByStatements(): array {
        return $this->queryOrderByStatements;
    }

    /**
     * @param \UniteCMS\CoreBundle\Query\QueryOrderBy[] $queryOrderByStatements
     */
    public function setQueryOrderByStatements(array $queryOrderByStatements): void {
        $this->queryOrderByStatements = $queryOrderByStatements;
    }
}
