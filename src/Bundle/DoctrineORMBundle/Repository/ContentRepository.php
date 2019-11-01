<?php

namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\DoctrineORMBundle\Content\ORMContentCriteria;

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
     * @param ORMContentCriteria $criteria
     *
     * @return array
     */
    public function typedFindBy(ORMContentCriteria $criteria) : array {

        $builder = $this->createQueryBuilder('c')
            ->select('c')
            ->setFirstResult($criteria->getFirstResult())
            ->setMaxResults($criteria->getMaxResults());

        $criteria->appendOrderBy($builder);
        $criteria->appendWhere($builder);

        $query = $builder->getQuery();
        return $query->execute();
    }

    /**
     * @param ORMContentCriteria $criteria
     *
     * @return int
     */
    public function typedCount(ORMContentCriteria $criteria) : int {

        $builder = $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->setFirstResult(0)
            ->setMaxResults(1);

        $criteria->appendOrderBy($builder);
        $criteria->appendWhere($builder);
        $query = $builder->getQuery();

        try {
            return $query->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return -1;
        }
    }
}
