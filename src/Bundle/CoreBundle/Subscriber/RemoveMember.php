<?php

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use UniteCMS\CoreBundle\Entity\OrganizationMember;

class RemoveMember
{

    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        /**
         * @var OrganizationMember $object
         */
        if ($object instanceof OrganizationMember) {

            // Remove all DomainMemberships for this user.
            foreach ($object->getOrganization()->getDomains() as $domain) {
                foreach ($domain->getMembers() as $domainMember) {
                    if ($domainMember->getAccessor() === $object->getUser()) {
                        $args->getEntityManager()->remove($domainMember);
                    }
                }
            }

        }
    }

}
