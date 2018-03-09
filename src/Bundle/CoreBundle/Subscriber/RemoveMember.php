<?php

namespace UnitedCMS\CoreBundle\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;

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
                foreach ($domain->getUsers() as $domainMember) {
                    if ($domainMember->getUser() === $object->getUser()) {
                        $args->getEntityManager()->remove($domainMember);
                    }
                }
            }

        }

        // Maybe we need to do something in the future on DomainMember delete here.
    }

}