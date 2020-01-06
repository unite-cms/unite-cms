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
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\DataFieldComparison;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;
use UniteCMS\CoreBundle\Query\DataFieldValue;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldComparison;
use UniteCMS\DoctrineORMBundle\Entity\Content;

class QueryExpressionVisitor extends BaseQueryExpressionVisitor
{
    /**
     * This method is a custom version of QueryBuilder::setCriteria.
     *
     * @param QueryBuilder $queryBuilder
     * @param ContentCriteria $criteria
     *
     * @return QueryExpressionVisitor|null
     * @throws \Doctrine\ORM\Query\QueryException
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
        foreach(static::findJoins($criteria->getWhereExpression(), $allAliases[0]) as $join) {
            $queryBuilder->leftJoin(
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

                $hasValidAlias = false;
                foreach($allAliases as $alias) {
                    if(strpos($sort . '.', $alias . '.') === 0) {
                        $hasValidAlias = true;
                        break;
                    }
                }

                if(!$hasValidAlias) {
                    $sort = $allAliases[0] . '.' . $sort;
                }

                if($orderBy instanceof DataFieldOrderBy) {
                    $sort = static::wrapJSONField($sort);
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
     *
     * @return Join[]
     * @throws \Doctrine\ORM\Query\QueryException
     */
    static function findJoins(?Expression $comparison, string $rootAlias) {

        $joins = [];

        if(empty($comparison)) {
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
                sprintf('%s = %s.id', self::wrapJSONField(join('.', [$rootAlias, $comparison->getField()])), $joined_table)
            );
        }

        if($comparison instanceof CompositeExpression) {
            foreach($comparison->getExpressionList() as $expression) {
                $joins = array_merge($joins, static::findJoins($expression, $rootAlias));
            }
        }

        return $joins;
    }

    /**
     * {@inheritDoc}
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function walkComparison(Comparison $comparison)
    {
        $op = $comparison->getOperator();
        $joined_table = null;
        $originalComparison = null;

        // Add joins for reference data field comparison
        if($comparison instanceof ReferenceDataFieldComparison) {

            // Add a join to the query.
            $joined_table = 'c_' . $comparison->getRootField();

            // Replace comparison with joined comparison.
            $originalComparison = $comparison;
            $comparison = new DataFieldComparison(
                $joined_table,
                $comparison->getOperator(),
                $comparison->getValue(),
                $comparison->getReferencedPath()
            );
        }

        // CONTAINS
        if($comparison instanceof DataFieldComparison && in_array($op, [Comparison::CONTAINS, ContentCriteria::NCONTAINS])) {
            $value = $this->walkValue($comparison->getValue());
            if(is_array($value)) {
                $value = $value[0];
            }
            $comparison = new Comparison($comparison->getField(), Comparison::EQ, $value);
            $expression = parent::walkComparison($comparison);

            return static::wrapJSONSearch(
                $expression->getLeftExpr(),
                $expression->getRightExpr()
            ) . ($op === Comparison::CONTAINS ? ' IS NOT NULL' : ' IS NULL');
        }

        $expression = parent::walkComparison($comparison);

        if($comparison instanceof DataFieldComparison) {

            // IS NULL and IS NOT NULL
            if (in_array($op, [Comparison::EQ, Comparison::IS, Comparison::NEQ]) && $this->walkValue($comparison->getValue()) === null) {

                if($joined_table) {
                    $pattern = '/[a-z_]+.' . str_replace($joined_table . '.', '', $comparison->getField()) . '/';
                } else {
                    $pattern = '/[a-z_]+.' . $comparison->getField() . '/';
                }

                if(preg_match($pattern, $expression, $matches) !== false) {

                    dump($matches);
                    $field = static::wrapJSONField($matches[0], true);

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
            }

            // IN, NOT IN
            else if(in_array($op, [Comparison::IN, Comparison::NIN])) {

                $pattern = '/[a-z].' . $comparison->getField() . '/';

                if(preg_match($pattern, $expression, $matches) !== false) {
                    $field = static::wrapJSONField($matches[0]);
                    $field .= ' ' . ($op === Comparison::IN ? 'IN' : 'NOT IN');
                    return new Func($field, $expression->getArguments());
                }
            }

            // Replace a field in a comparison
            else if ($expression instanceof \Doctrine\ORM\Query\Expr\Comparison) {
                return new \Doctrine\ORM\Query\Expr\Comparison(
                    static::wrapJSONField($expression->getLeftExpr(), true),
                    $expression->getOperator(),
                    static::wrapJSONValue($expression->getRightExpr())
                );
            }
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
     * @param string $field
     * @param bool $unquote
     * @return string
     *
     * @throws QueryException
     */
    static function wrapJSONField(string $field, $unquote = false) : string {
        $parts = explode('.', $field);

        if(count($parts) < 2) {
            throw new QueryException('Field must contain an alias and a JSON path.');
        }

        $alias = array_shift($parts);
        $field = join('.', $parts);
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

    /**
     * @param string $field
     * @param string $value
     * @return string
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    static function wrapJSONSearch(string $field, string $value) : string {
        $parts = explode('.', $field);

        if(count($parts) < 2) {
            throw new QueryException('Field must contain an alias and a JSON path.');
        }

        $alias = array_shift($parts);
        $field = join('.', $parts);

        return sprintf("JSON_SEARCH(%s.data, 'one', %s, NULL, '$.%s')", $alias, $value, $field);
    }
}
