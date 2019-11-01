<?php


namespace UniteCMS\DoctrineORMBundle\Content;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\QueryExpressionVisitor;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentCriteria;

class ORMContentCriteria extends ContentCriteria
{
    static function fromContentCriteria(ContentCriteria $criteria, string $type, bool $includeDeleted = false) : ORMContentCriteria {
        $ormCriteria = new self();

        $ormCriteria->orderBy($criteria->getOrderings());
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
     */
    public function appendOrderBy(QueryBuilder $queryBuilder) : QueryBuilder {
        foreach($this->getOrderings() as $field => $value) {
            $queryBuilder->addOrderBy(
                $this->transformField($field, $queryBuilder->getRootAliases()[0]),
                $value
            );
        }

        return $queryBuilder;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function appendWhere(QueryBuilder $queryBuilder) : QueryBuilder {

        // TODO: Refactor to one visitor
        $visitor = new QueryExpressionVisitor($queryBuilder->getAllAliases());
        $ormExpressionVisitor = new ORMExpressionVisitor();
        $queryBuilder->andWhere(
            $visitor->dispatch(
                $ormExpressionVisitor->dispatch($this->getWhereExpression())
            )
        );
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
