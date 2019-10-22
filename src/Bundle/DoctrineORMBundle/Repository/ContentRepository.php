<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
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
     * @param string $type
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     * @param bool $includeDeleted
     *
     * @return array
     */
    public function typedFindBy(string $type, array $criteria, array $orderBy = null, $limit = null, $offset = null, bool $includeDeleted = false) : array {

        if(!$includeDeleted) {
            $criteria['deleted'] = null;
        }

        return $this->findBy(array_merge($criteria, ['type' => $type]), $orderBy, $limit, $offset);
    }

    /**
     * @param string $type
     * @param array $criteria
     * @param bool $includeDeleted
     *
     * @return int
     */
    public function typedCount(string $type, array $criteria, bool $includeDeleted = false) : int {

        if(!$includeDeleted) {
            $criteria['deleted'] = null;
        }

        return $this->count(array_merge($criteria, ['type' => $type]));
    }
}
