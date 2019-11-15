<?php

namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\DoctrineORMBundle\Query\QueryExpressionVisitor;

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
     * @param string $type
     * @param ContentCriteria $criteria
     * @param bool $includeDeleted
     *
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function typedFindBy(string $type, ContentCriteria $criteria, bool $includeDeleted) : array {

        $builder = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.type = :type')->setParameter('type', $type)
            ->setFirstResult($criteria->getFirstResult())
            ->setMaxResults($criteria->getMaxResults());

        if(!$includeDeleted) {
            $builder->andWhere('c.deleted IS NULL');
        }

        // Set order by and where expressions to builder.
        QueryExpressionVisitor::apply($builder, $criteria);

        $query = $builder->getQuery();
        return $query->execute();
    }

    /**
     * @param string $type
     * @param ContentCriteria $criteria
     * @param bool $includeDeleted
     *
     * @return int
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function typedCount(string $type, ContentCriteria $criteria, bool $includeDeleted) : int {

        $builder = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('c.type = :type')->setParameter('type', $type)
            ->setFirstResult(0)
            ->setMaxResults(1);

        if(!$includeDeleted) {
            $builder->andWhere('c.deleted IS NULL');
        }

        // Set order by and where expressions to builder.
        QueryExpressionVisitor::apply($builder, $criteria);
        $query = $builder->getQuery();

        try {
            return $query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return -1;
        }
    }
}
