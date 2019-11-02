<?php


namespace UniteCMS\DoctrineORMBundle\Content;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\QueryException;
use UniteCMS\DoctrineORMBundle\Query\QueryExpressionVisitor;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Query\QueryCriteria;

class ORMQueryCriteria extends QueryCriteria
{
    static function fromContentCriteria(QueryCriteria $criteria, string $type, bool $includeDeleted = false) : ORMQueryCriteria {
        $ormCriteria = new self();

        $ormCriteria->setQueryOrderByStatements($criteria->getQueryOrderByStatements());
        $ormCriteria->where(new Comparison('type', Comparison::EQ, $type));

        if(!$includeDeleted) {
            $ormCriteria->andWhere(new Comparison('deleted', Comparison::EQ, new Value(null)));
        }

        if($criteria->getWhereExpression()) {
            $ormCriteria->andWhere($criteria->getWhereExpression());
        }

        return $ormCriteria;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @return \Doctrine\ORM\QueryBuilder
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function applyToQueryBuilder(QueryBuilder $queryBuilder) : QueryBuilder {

        // Copied and modified from QueryBuilder::addCriteria()
        $allAliases = $queryBuilder->getAllAliases();
        if ( ! isset($allAliases[0])) {
            throw new QueryException('No aliases are set before invoking addCriteria().');
        }

        $visitor = new QueryExpressionVisitor($queryBuilder->getAllAliases());

        if ($whereExpression = $this->getWhereExpression()) {
            $queryBuilder->andWhere($visitor->dispatch($whereExpression));
            foreach ($visitor->getParameters() as $parameter) {
                $queryBuilder->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
            }
        }

        if ($this->getQueryOrderByStatements()) {
            foreach ($this->getQueryOrderByStatements() as $sortBy) {

                $hasValidAlias = false;
                $sort = $sortBy->getField('');
                foreach($allAliases as $alias) {
                    if(strpos($sort . '.', $alias . '.') === 0) {
                        $hasValidAlias = true;
                        break;
                    }
                }

                if(!$hasValidAlias) {
                    $sort = $sortBy->getField($allAliases[0]);
                }

                $queryBuilder->addOrderBy($sort, $sortBy->getOrder());
            }
        }

        return $queryBuilder;
    }

    /**
     * @param string $field
     * @param string $alias
     *
     * @return string
     */
    protected function transformField(string $field, string $alias) : string {
        switch ($field) {
            case 'id':
            case 'type':
            case 'deleted':
            case 'username':
                return join('.', [$alias, $field]);

            case 'sensitive_data':
            case 'password_reset_token':
                throw new InvalidArgumentException();

            default: return $this->transformJSONField($field, $alias);
        }
    }

    /**
     * @param string $field
     * @param string $alias
     *
     * @return string
     */
    protected function transformJSONField(string $field, string $alias) : string {
        return sprintf("JSON_EXTRACT(%s.data, '$.%s')", $alias, $field);
    }
}
