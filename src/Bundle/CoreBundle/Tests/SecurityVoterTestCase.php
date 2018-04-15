<?php

namespace UniteCMS\CoreBundle\Tests;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;

abstract class SecurityVoterTestCase extends ContainerAwareTestCase
{

    /**
     * @var TokenInterface[] $u
     */
    protected $u = [];

    /**
     * @var Organization $org1
     */
    protected $org1;

    /**
     * @var Organization $org2
     */
    protected $org2;

    public function setUp()
    {
        parent::setUp();

        $this->org1 = new Organization();
        $this->org1->setIdentifier('org1')->setId(1);
        $this->org2 = new Organization();
        $this->org2->setIdentifier('org2')->setId(2);

        $platformUser = new User();
        $platformUser->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->u['platform'] = new UsernamePasswordToken($platformUser, 'password', 'main', $platformUser->getRoles());

        $adminUser = new User();
        $adminUser->setRoles([User::ROLE_USER]);
        $adminUserMember = new OrganizationMember();
        $adminUserMember->setRoles([Organization::ROLE_ADMINISTRATOR])->setOrganization($this->org2);
        $adminUser->addOrganization($adminUserMember);
        $this->u['admin'] = new UsernamePasswordToken($adminUser, 'password', 'main', $adminUser->getRoles());

        $user = new User();
        $user->setRoles([User::ROLE_USER]);
        $userMember = new OrganizationMember();
        $userMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $user->addOrganization($userMember);
        $this->u['user'] = new UsernamePasswordToken($user, 'password', 'main', $user->getRoles());

        $this->u['anonymous'] = new AnonymousToken('foo', 'baa');

        $user = new User();
        $user->setRoles(['non_supported_role']);
        $userMember = new OrganizationMember();
        $userMember->setRoles(['non_supported_role'])->setOrganization($this->org2);
        $user->addOrganization($userMember);
        $this->u['non_supported_role'] = new UsernamePasswordToken($user, 'password', 'main', $user->getRoles());

        $this->u['non_supported_role'] = new AnonymousToken('foo', 'baa');
    }
}
