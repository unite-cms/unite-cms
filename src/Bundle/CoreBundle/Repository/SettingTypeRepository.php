<?php

namespace UniteCMS\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SettingTypeRepository
 */
class SettingTypeRepository extends EntityRepository
{
    public function findByIdentifiers($organization, $domain, $settingType)
    {
        $result = $this->createQueryBuilder('st')
            ->select('st', 'dm', 'org')
            ->join('st.domain', 'dm')
            ->join('dm.organization', 'org')
            ->where('org.identifier = :organization')
            ->andWhere('dm.identifier = :domain')
            ->andWhere('st.identifier = :settingType')
            ->setParameters(
                [
                    'organization' => $organization,
                    'domain' => $domain,
                    'settingType' => $settingType,
                ]
            )
            ->getQuery()->getResult();

        return (count($result) > 0) ? $result[0] : null;
    }
}
