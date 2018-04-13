<?php

namespace UniteCMS\CoreBundle\Repository;

use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * SettingTypeRepository
 */
class SettingTypeRepository extends SortableRepository
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
