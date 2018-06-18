<?php

namespace UniteCMS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

/**
 * ViewRepository
 */
class ViewRepository extends EntityRepository
{
    public function findByIdentifiers($organization, $domain, $contentType, $view)
    {
        $result = $this->createQueryBuilder('v')
            ->select('v', 'ct', 'dm', 'org')
            ->join('v.contentType', 'ct')
            ->join('ct.domain', 'dm')
            ->join('dm.organization', 'org')
            ->where('org.identifier = :organization')
            ->andWhere('dm.identifier = :domain')
            ->andWhere('ct.identifier = :contentType')
            ->andWhere('v.identifier = :view')
            ->setParameters(
                [
                    'organization' => IdentifierNormalizer::normalize($organization),
                    'domain' => IdentifierNormalizer::normalize($domain),
                    'contentType' => IdentifierNormalizer::normalize($contentType),
                    'view' => IdentifierNormalizer::normalize($view),
                ]
            )
            ->getQuery()->getResult();

        return (count($result) > 0) ? $result[0] : null;
    }
}
