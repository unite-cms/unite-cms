<?php


namespace UniteCMS\DoctrineORMBundle\Content;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentCriteria;

class ORMContentCriteria extends ContentCriteria
{
    protected $customOrderBy = [];

    static function fromContentCriteria(ContentCriteria $criteria, string $type, bool $includeDeleted = false) : ORMContentCriteria {
        $ormCriteria = new self();

        $ormCriteria->setCustomOrderBy($criteria->getOrderings());
        $ormCriteria->where(new Comparison('type', Comparison::EQ, $type));

        if(!$includeDeleted) {
            $ormCriteria->andWhere(new Comparison('deleted', Comparison::EQ, new Value(null)));
        }

        // Transform where filters.
        if($criteria->getWhereExpression()) {
            $ormCriteria->andWhere($criteria->getWhereExpression());
        }

        return $ormCriteria;
    }

    /**
     * @return array
     */
    public function getCustomOrderBy() : array {
        return $this->customOrderBy;
    }

    /**
     * @param array $customOrderBy
     * @return $this
     */
    public function setCustomOrderBy(array $customOrderBy) : self {
        $this->customOrderBy = $customOrderBy;
        return $this;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function appendOrderBy(QueryBuilder $queryBuilder) : QueryBuilder {
        foreach($this->getCustomOrderBy() as $field => $value) {
            $queryBuilder->addOrderBy(
                $this->transformField($field, $queryBuilder->getRootAliases()[0]),
                $value
            );
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
