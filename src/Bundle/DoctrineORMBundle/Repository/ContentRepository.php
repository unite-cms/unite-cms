<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;

class ContentRepository extends EntityRepository
{

    /**
     * @param string $type
     * @param $id
     * @param bool $includeDeleted
     *
     * @return ContentInterface|null
     */
    public function typedFind(string $type, $id, bool $includeDeleted = false) : ?ContentInterface {

        $criteria = [
            'type' => $type,
            'id' => $id,
        ];

        if(!$includeDeleted) {
            $criteria['deleted'] = null;
        }

        $result = $this->findOneBy($criteria);
        return $result && $result instanceof ContentInterface ? $result : null;
    }

    /**
     * Add a parameter to the parameters array and return the next key.
     *
     * @param array $parameters
     * @param $value
     *
     * @return string
     */
    static function addParameter(array &$parameters, $value) : string {
        $pKey = sprintf('p%s', count($parameters) + 1);
        $parameters[$pKey] = $value;
        return $pKey;
    }

    /**
     * Internally build where filter.
     *
     * @param QueryBuilder $queryBuilder
     * @param ContentFilterInput $criteria
     * @param array $parameters
     *
     * @return QueryBuilder
     */
    protected function buildWhereFilter(QueryBuilder $queryBuilder, ContentFilterInput $criteria, array &$parameters) : QueryBuilder {

        // Append where filter
        if($criteria->getField() && $criteria->getValue()) {
            $queryBuilder->andWhere(sprintf("JSON_EXTRACT(c.data, '$.%s') = :%s", $criteria->getField(), static::addParameter($parameters, $criteria->getValue())));
        }

        // Append AND filter
        if(!empty($criteria->getAND())) {
            $andFilter = [];
            foreach($criteria->getAND() as $filterInput) {
                $this->buildWhereFilter($queryBuilder, $filterInput, $parameters);
            }
            $queryBuilder->andWhere(new Andx($andFilter));
        }

        // Append OR filter
        if(!empty($criteria->getOR())) {
            $orFilter = [];
            foreach($criteria->getOR() as $filterInput) {
                $this->buildWhereFilter($queryBuilder, $filterInput, $parameters);
            }
            $queryBuilder->andWhere(new Orx($orFilter));
        }

        return $queryBuilder;
    }

    /**
     * Internally build a query
     *
     * @param QueryBuilder $queryBuilder
     * @param string $type
     * @param ContentFilterInput $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @param bool $includeDeleted
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function buildQuery(QueryBuilder $queryBuilder, string $type, ContentFilterInput $criteria = null, array $orderBy = null, $limit = null, $offset = null, bool $includeDeleted = false) : QueryBuilder {

        $parameters = [];

        // Add basic type where statement
        $queryBuilder->where(sprintf('c.type = :%s', static::addParameter($parameters, $type)));

        // Add order by
        if(!empty($orderBy)) {
            foreach($orderBy as $order) {
                $queryBuilder->addOrderBy($order['field'], $order['desc'] ? 'DESC' : 'ASC');
            }
        }

        // Add include deleted
        if(!$includeDeleted) {
            $queryBuilder->andWhere('c.deleted IS NULL');
        }

        // Add limit
        if(!empty($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        // Add offset
        if(!empty($offset)) {
            $queryBuilder->setFirstResult($offset);
        }

        // Add criteria
        if(!empty($criteria)) {
            $this->buildWhereFilter($queryBuilder, $criteria, $parameters);
        }

        dump($queryBuilder->getDQL());
        // Append all parameters
        return $queryBuilder->setParameters($parameters);
    }

    /**
     * @param string $type
     * @param ContentFilterInput $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @param bool $includeDeleted
     *
     * @return array
     */
    public function typedFindBy(string $type, ContentFilterInput $criteria = null, array $orderBy = null, $limit = null, $offset = null, bool $includeDeleted = false) : array {
        return $this->buildQuery(
            $this->createQueryBuilder('c')->select('c'),
            $type,
            $criteria,
            $orderBy,
            $limit,
            $offset,
            $includeDeleted
        )->getQuery()->execute();
    }

    /**
     * @param string $type
     * @param ContentFilterInput $criteria
     * @param bool $includeDeleted
     *
     * @return int
     */
    public function typedCount(string $type, ContentFilterInput $criteria = null, bool $includeDeleted = false) : int {
        try {
            return $this->buildQuery(
                $this->createQueryBuilder('c')->select('COUNT(c)'),
                $type,
                $criteria,
                null,
                null,
                null,
                $includeDeleted
            )->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return -1;
        }
    }
}
