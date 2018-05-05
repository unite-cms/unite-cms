<?php

namespace UniteCMS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DomainMemberTypeRepository
 */
class DomainMemberTypeRepository extends EntityRepository
{
    public function findByIdentifiers($organization, $domain, $domainMemberType)
    {
        $result = $this->createQueryBuilder('dmt')
            ->select('dmt', 'dm', 'org')
            ->join('dmt.domain', 'dm')
            ->join('dm.organization', 'org')
            ->where('org.identifier = :organization')
            ->andWhere('dm.identifier = :domain')
            ->andWhere('dmt.identifier = :domainMemberType')
            ->setParameters(
                [
                    'organization' => $organization,
                    'domain' => $domain,
                    'domainMemberType' => $domainMemberType,
                ]
            )
            ->getQuery()->getResult();

        return (count($result) > 0) ? $result[0] : null;
    }
}
