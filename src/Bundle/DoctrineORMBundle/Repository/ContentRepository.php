<?php

namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\DoctrineORMBundle\Content\ORMQueryCriteria;

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
     * @param ORMQueryCriteria $criteria
     *
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function typedFindBy(ORMQueryCriteria $criteria) : array {

        $builder = $this->createQueryBuilder('c')
            ->select('c')
            ->setFirstResult($criteria->getFirstResult())
            ->setMaxResults($criteria->getMaxResults());

        $query = $criteria
            ->applyToQueryBuilder($builder)
            ->getQuery();

        return $query->execute();
    }

    /**
     * @param ORMQueryCriteria $criteria
     *
     * @return int
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function typedCount(ORMQueryCriteria $criteria) : int {

        $builder = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->setFirstResult(0)
            ->setMaxResults(1);

        $query = $criteria
            ->applyToQueryBuilder($builder)
            ->getQuery();

        dump($query);

        try {
            return $query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return -1;
        }
    }
}
