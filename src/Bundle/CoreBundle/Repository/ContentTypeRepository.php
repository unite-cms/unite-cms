<?php

namespace UniteCMS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

/**
 * ContentTypeRepository
 */
class ContentTypeRepository extends EntityRepository
{
    public function findByIdentifiers($organization, $domain, $contentType)
    {
        $result = $this->createQueryBuilder('ct')
            ->select('ct', 'dm', 'org')
            ->join('ct.domain', 'dm')
            ->join('dm.organization', 'org')
            ->where('org.identifier = :organization')
            ->andWhere('dm.identifier = :domain')
            ->andWhere('ct.identifier = :contentType')
            ->setParameters(
                [
                    'organization' => IdentifierNormalizer::normalize($organization),
                    'domain' => IdentifierNormalizer::normalize($domain),
                    'contentType' => IdentifierNormalizer::normalize($contentType),
                ]
            )
            ->getQuery()->getResult();

        return (count($result) > 0) ? $result[0] : null;
    }
}
