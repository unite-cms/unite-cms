<?php

namespace UnitedCMS\CoreBundle\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;

class AddMember
{

    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        /**
         * @var DomainMember $object
         */
        if ($object instanceof DomainMember) {

            // If a user was invited to a domain, that user must also become member of the organization.
            $alreadyMember = false;

            foreach($object->getUser()->getOrganizations() as $organizationMember) {
                if($object->getDomain()->getOrganization() === $organizationMember->getOrganization()) {
                    $alreadyMember = true;
                }
            }

            if(!$alreadyMember) {
                $organizationMember = new OrganizationMember();
                $organizationMember->setOrganization($object->getDomain()->getOrganization());
                $object->getUser()->addOrganization($organizationMember);
            }
        }
    }

}