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
        $expression = parent::walkComparison($comparison);

        if($comparison instanceof DataFieldComparison) {

            // Replace a field in a string (e.g. "c.title IS NULL)"
            if(is_string($expression)) {
                $pattern = '/[a-z].' . $comparison->getField() . '/';

                if(preg_match($pattern, $expression, $matches) !== false) {
                    $parts = preg_split($pattern, $expression);
                    return $parts[0] . static::wrapJSONField($matches[0]) . $parts[1];
                }

                throw new QueryException(sprintf('Could not replace JSON field in expression "%s".', $expression));
            }

            // Replace a field in a func string (e.g. "c.title IS NULL)"
            else if ($expression instanceof Func) {
                $pattern = '/[a-z].' . $comparison->getField() . '/';

                if(preg_match($pattern, $expression->getName(), $matches) !== false) {
                    $parts = preg_split($pattern, $expression->getName());
                    return new Func(
                        $parts[0] . static::wrapJSONField($matches[0]) . $parts[1],
                        $expression->getArguments()
                    );
                }

                throw new QueryException(sprintf('Could not replace JSON field in func "%s".', $expression));
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

        return sprintf("JSON_EXTRACT(%s.data, '$.%s')", $alias, $field);
    }
}
