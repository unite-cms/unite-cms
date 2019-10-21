<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\DoctrineORMBundle\Entity\Content;

class ContentRepository extends EntityRepository
{
    public function typedFind(string $type, $id) : ?ContentInterface {
        $result = $this->findOneBy([
            'type' => $type,
            'id' => $id,
        ]);

        return $result && $result instanceof ContentInterface ? $result : null;
    }

    public function typedFindBy(string $type, array $criteria, array $orderBy = null, $limit = null, $offset = null) : array {
        return $this->findBy(array_merge($criteria, ['type' => $type]), $orderBy, $limit, $offset);
    }

    public function typedCount(string $type, array $criteria) : int {
        return $this->count(array_merge($criteria, ['type' => $type]));
    }
}
