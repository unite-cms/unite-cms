<?php


namespace UniteCMS\DoctrineORMBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UniteCMS\DoctrineORMBundle\Entity\User;

class UserRepository extends EntityRepository {

    public function typedFindByUsername(string $type, string $username) : ?User {
        $result = $this->findOneBy([
            'type' => $type,
            'username' => $username,
        ]);

        return $result && $result instanceof User ? $result : null;
    }

}
