<?php

namespace UniteCMS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

/**
 * ApiKeyRepository
 */
class ApiKeyRepository extends EntityRepository
{
    public function findOneByTokenAndOrganization(string $token, string $organization)
    {
        $result = $this->createQueryBuilder('a')
            ->select('a')
            ->join('a.organization', 'org')
            ->where('a.token = :token')
            ->andWhere('org.identifier = :organization')
            ->setParameters(
                [
                    'token' => $token,
                    'organization' => IdentifierNormalizer::normalize($organization),
                ]
            )
            ->getQuery()->getResult();

        return (count($result) > 0) ? $result[0] : null;
    }
}
