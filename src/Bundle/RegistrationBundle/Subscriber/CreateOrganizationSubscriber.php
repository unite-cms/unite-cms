<?php

namespace UniteCMS\RegistrationBundle\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;

class CreateOrganizationSubscriber
{

    private $securityTokenStorage;

    public function __construct(TokenStorage $securityTokenStorage)
    {
        $this->securityTokenStorage = $securityTokenStorage;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        /**
         * @var Organization $object
         */
        if ($object instanceof Organization) {

            if (null === $token = $this->securityTokenStorage->getToken()) {
                return;
            }

            if (!is_object($user = $token->getUser())) {
                return;
            }

            if(!$user instanceof User) {
                return;
            }

            if(in_array(User::ROLE_PLATFORM_ADMIN, $user->getRoles())) {
                return;
            }

            $orgMembership = new OrganizationMember();
            $orgMembership
                ->setOrganization($object)
                ->setUser($user)
                ->setSingleRole(Organization::ROLE_ADMINISTRATOR);

            $args->getEntityManager()->persist($orgMembership);
        }
    }

}
