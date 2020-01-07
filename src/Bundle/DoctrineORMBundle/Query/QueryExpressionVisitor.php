<?php

namespace UniteCMS\DoctrineORMBundle\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\QueryExpressionVisitor as BaseQueryExpressionVisitor;
use Doctrine\ORM\QueryBuilder;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;
use UniteCMS\CoreBundle\Query\BaseFieldOrderBy;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\DataFieldComparison;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;
use UniteCMS\CoreBundle\Query\DataFieldValue;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldComparison;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldOrderBy;
use UniteCMS\DoctrineORMBundle\Entity\Content;

class QueryExpressionVisitor extends BaseQueryExpressionVisitor
{
    protected $_queryAliases;

    /**
     * This method is a custom version of QueryBuilder::setCriteria.
     *
     * @param QueryBuilder $queryBuilder
     * @param ContentCriteria $criteria
     *
     * @return QueryExpressionVisitor|null
     * @throws QueryException
     */
    static function apply(QueryBuilder $queryBuilder, ContentCriteria $criteria) : ?QueryExpressionVisitor {

        if(empty($criteria->getOrderings()) && empty($criteria->getWhereExpression())) {
            return null;
        }

        $allAliases = $queryBuilder->getAllAliases();

        if ( ! isset($allAliases[0])) {
            throw new QueryException('No aliases are set before invoking addCriteria().');
        }

        // First of all find all joins
        foreach(static::findJoins($criteria->getWhereExpression(), $criteria->getOrderings(), $allAliases[0]) as $join) {
            $queryBuilder->innerJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $join->getCondition(),
                $join->getIndexBy()
            );
        }

        // Get aliases again, because joined tables could have added some aliases.
        $allAliases = $queryBuilder->getAllAliases();

        $visitor = new self($allAliases);

        // Set order by to query builder.
        if ($criteria->getOrderings()) {
            foreach ($criteria->getOrderings() as $orderBy) {

                $sort = $orderBy->getField();
                $order = $orderBy->getOrder();
                $alias = $allAliases[0];

                if($orderBy instanceof ReferenceDataFieldOrderBy) {
                    $sort = static::wrapJSONField('c_' . $orderBy->getRootField(), $sort, false);
                } else if($orderBy instanceof DataFieldOrderBy) {
                    $sort = static::wrapJSONField($alias, $sort, false);
                } else {
                    $sort = $alias . '.' . $sort;
                }

                $queryBuilder->addOrderBy($sort, $order);
            }
        }

        // Set where to query builder.
        if($criteria->getWhereExpression()) {
            $queryBuilder->andWhere($visitor->dispatch($criteria->getWhereExpression()));

            /**
             * @var Parameter $parameter
             */
            foreach ($visitor->getParameters() as $parameter) {

                $queryBuilder->setParameter(
                    $parameter->getName(),
                    $parameter->getValue(),
                    $parameter->getType()
                );
            }
        }

        return $visitor;
    }

    /**
     * @param Expression $comparison
     * @param string $rootAlias
     * @param BaseFieldOrderBy[] $orderings
     *
     * @return Join[]
     * @throws QueryException
     */
    static function findJoins(?Expression $comparison, array $orderings, string $rootAlias) {

        $joins = [];

        if(empty($comparison) && empty($orderings)) {
            return $joins;
        }

        // Add joins for reference data field comparison
        if($comparison instanceof ReferenceDataFieldComparison) {

            // Add a join to the query.
            $joined_table = 'c_' . $comparison->getRootField();

            $joins[$joined_table] = new Join(
                Join::LEFT_JOIN,
                Content::class,
                $joined_table,
                Join::WITH,
                sprintf('%s = %s.id', self::wrapJSONField($rootAlias, $comparison->getRootField() . '.data'), $joined_table)
            );
        }

        if($comparison instanceof CompositeExpression) {
            foreach($comparison->getExpressionList() as $expression) {
                $joins = array_merge($joins, static::findJoins($expression, [], $rootAlias));
            }
        }

        foreach($orderings as $ordering) {
            if($ordering instanceof ReferenceDataFieldOrderBy) {

                // Add a join to the query.
                $joined_table = 'c_' . $ordering->getRootField();

                $joins[$joined_table] = new Join(
                    Join::LEFT_JOIN,
                    Content::class,
                    $joined_table,
                    Join::WITH,
                    sprintf('%s = %s.id', self::wrapJSONField($rootAlias, $ordering->getRootField() . '.data'), $joined_table)
                );
            }
        }

        return $joins;
    }

    /**
     * Constructor
     *
     * @param array $queryAliases
     */
    public function __construct($queryAliases)
    {
        $this->_queryAliases = $queryAliases;
        parent::__construct($queryAliases);
    }

    /**
     * {@inheritDoc}
     * @throws QueryException
     */
    public function walkComparison(Comparison $comparison)
    {
        // Prepare comparision parts
        $alias = $this->_queryAliases[0];
        $op = $comparison->getOperator();
        $field = $comparison->getField();

        // Transform a referenced data field comparison to a normal comparison, using th join table.
        if($comparison instanceof ReferenceDataFieldComparison) {
            $alias = 'c_' . $comparison->getRootField();
            $field = $comparison->getField();
            $referencedPath = $comparison->getReferencedPath();
            $rootField = array_shift($referencedPath);

            // If this is a referenced base field, we transform it to ba base field.
            if(in_array($rootField, ContentCriteria::BASE_FIELDS)) {
                $comparison = new BaseFieldComparison($alias . '.' . $rootField, $op, $comparison->getValue());
            }
        }

        // Do the doctrine core transformation.
        $expression = parent::walkComparison($comparison);

        // Base field comparison can be just returned.
        if(!$comparison instanceof DataFieldComparison) {

            // If this base field comparison has a custom operator
            if($comparison instanceof BaseFieldComparison && $comparison->getCustomOperator() && $comparison->getCustomOperator() === ContentCriteria::NCONTAINS) {
                return 'NOT ' . $expression;
            }

            return $expression;
        }

        // CONTAINS
        if(in_array($op, [Comparison::CONTAINS])) {
            return new \Doctrine\ORM\Query\Expr\Comparison(
                sprintf('UPPER(%s)', static::wrapJSONField($alias, $field)),
                $comparison->getCustomOperator() === ContentCriteria::NCONTAINS ? 'NOT LIKE' : 'LIKE',
                sprintf('UPPER(%s)', static::wrapJSONValue($expression->getRightExpr()))
            );
        }

        // IS NULL and IS NOT NULL
        if (in_array($op, [Comparison::EQ, Comparison::IS, Comparison::NEQ]) && $this->walkValue($comparison->getValue()) === null) {

            $field = static::wrapJSONField($alias, $field);

            // IS NOT NULL
            if($op === Comparison::NEQ) {
                return sprintf('%1$s IS NOT NULL AND %1$s != \'null\'', $field);
            }

            // IS NULL
            if($op === Comparison::EQ || $op === Comparison::IS) {
                return sprintf('%1$s IS NULL OR %1$s = \'null\'', $field);
            }

            throw new QueryException('Null value can only be used with NEQ, EQ or IS operator.');
        }

        // IN, NOT IN
        else if(in_array($op, [Comparison::IN, Comparison::NIN])) {
            $field = static::wrapJSONField($alias, $field);
            $field .= ' ' . ($op === Comparison::IN ? 'IN' : 'NOT IN');
            return new Func($field, $expression->getArguments());
        }

        // Replace a field in a comparison
        else if ($expression instanceof \Doctrine\ORM\Query\Expr\Comparison) {
            return new \Doctrine\ORM\Query\Expr\Comparison(
                static::wrapJSONField($alias, $field),
                $expression->getOperator(),
                static::wrapJSONValue($expression->getRightExpr())
            );
        }

        return $expression;
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        if($value instanceof DataFieldValue) {
            if(is_bool($value->getValue())) {
                return $value->getValue() ? 'true' : 'false';
            }
            if(is_numeric($value->getValue())) {
                return '' . $value->getValue();
            }
        }

        return parent::walkValue($value);
    }

    /**
     * @param string $alias
     * @param string $field
     * @param bool $unquote
     * @return string
     */
    static function wrapJSONField(string $alias, string $field, bool $unquote = true) : string {
        $field = sprintf("JSON_EXTRACT(%s.data, '$.%s')", $alias, $field);
        return $unquote ? sprintf('JSON_UNQUOTE(%s)', $field) : $field;
    }

    /**
     * @param $value
     * @return string
     */
    static function wrapJSONValue($value) : string {
        return sprintf("JSON_UNQUOTE(%s)", $value);
    }
}
