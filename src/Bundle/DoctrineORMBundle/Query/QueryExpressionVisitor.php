<?php

namespace UniteCMS\DoctrineORMBundle\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\QueryExpressionVisitor as BaseQueryExpressionVisitor;
use Doctrine\ORM\QueryBuilder;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\DataFieldComparison;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;

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
     * {@inheritDoc}
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function walkComparison(Comparison $comparison)
    {
        $op = $comparison->getOperator();

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

                $pattern = '/[a-z].' . $comparison->getField() . '/';

                if(preg_match($pattern, $expression, $matches) !== false) {

                    $field = static::wrapJSONField($matches[0]);

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
                    static::wrapJSONField($expression->getLeftExpr()),
                    $expression->getOperator(),
                    $expression->getRightExpr()
                );
            }
        }

        return $expression;
    }

    /**
     * @param string $field
     * @return string
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    static function wrapJSONField(string $field) : string {
        $parts = explode('.', $field);

        if(count($parts) < 2) {
            throw new QueryException('Field must contain an alias and a JSON path.');
        }

        $alias = array_shift($parts);
        $field = join('.', $parts);

        return sprintf("JSON_UNQUOTE(JSON_EXTRACT(%s.data, '$.%s'))", $alias, $field);
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
