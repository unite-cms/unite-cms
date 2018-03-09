<?php

namespace src\UnitedCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Security\DomainVoter;
use UnitedCMS\CoreBundle\Tests\SecurityVoterTestCase;

class DomainVoterTest extends SecurityVoterTestCase
{

    /**
     * @var Domain
     */
    protected $domain1;

    /**
     * @var Domain
     */
    protected $domain2;

    public function setUp()
    {
        parent::setUp();

        $this->domain1 = new Domain();
        $this->domain1->setOrganization($this->org1)->setId(1);

        $this->domain2 = new Domain();
        $this->domain2->setOrganization($this->org2)->setId(2);

        $admin = new User();
        $admin->setRoles([User::ROLE_USER]);
        $adminMember = new OrganizationMember();
        $adminMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $adminDomainMember = new DomainMember();
        $adminDomainMember->setRoles([Domain::ROLE_ADMINISTRATOR])->setDomain($this->domain1);
        $admin->addOrganization($adminMember);
        $admin->addDomain($adminDomainMember);
        $this->u['domain_admin'] = new UsernamePasswordToken($admin, 'password', 'main', $admin->getRoles());

        $user = new User();
        $user->setRoles([User::ROLE_USER]);
        $userMember = new OrganizationMember();
        $userMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $userDomainMember = new DomainMember();
        $userDomainMember->setRoles([Domain::ROLE_EDITOR])->setDomain($this->domain1);
        $user->addOrganization($userMember);
        $user->addDomain($userDomainMember);
        $this->u['domain_editor'] = new UsernamePasswordToken($user, 'password', 'main', $user->getRoles());
    }

    public function testCRUDActions()
    {

        $dm = $this->container->get('security.authorization_checker');

        // Platform admins can preform all actions.
        $this->container->get('security.token_storage')->setToken($this->u['platform']);
        $this->assertTrue($dm->isGranted([DomainVoter::CREATE], Domain::class));
        $this->assertTrue($dm->isGranted([DomainVoter::LIST], Domain::class));
        $this->assertTrue($dm->isGranted([DomainVoter::VIEW], $this->domain1));
        $this->assertTrue($dm->isGranted([DomainVoter::UPDATE], $this->domain1));
        $this->assertTrue($dm->isGranted([DomainVoter::DELETE], $this->domain1));

        // Organization Admins are allowed to add new Domains.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertFalse($dm->isGranted([DomainVoter::CREATE], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertFalse($dm->isGranted([DomainVoter::CREATE], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['user']);
        $this->assertFalse($dm->isGranted([DomainVoter::CREATE], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([DomainVoter::CREATE], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([DomainVoter::CREATE], Domain::class));


        // Add Organization Users are allowed to list Domains.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([DomainVoter::LIST], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([DomainVoter::LIST], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['user']);
        $this->assertTrue($dm->isGranted([DomainVoter::LIST], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([DomainVoter::LIST], Domain::class));

        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([DomainVoter::LIST], Domain::class));

        // Organization Admins are allowed to view/update/delete all domains of his_her organization.
        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([DomainVoter::VIEW], $this->domain2));
        $this->assertTrue($dm->isGranted([DomainVoter::UPDATE], $this->domain2));
        $this->assertTrue($dm->isGranted([DomainVoter::DELETE], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::VIEW], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::UPDATE], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain1));

        // Organization Members are not allowed to view/update/delete domains without a membership.
        $this->container->get('security.token_storage')->setToken($this->u['user']);
        $this->assertFalse($dm->isGranted([DomainVoter::VIEW], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::UPDATE], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain2));

        // Domain Admins are allowed to view/update their domains.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([DomainVoter::VIEW], $this->domain1));
        $this->assertTrue($dm->isGranted([DomainVoter::UPDATE], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::VIEW], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::UPDATE], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain2));

        // Domain Members are allowed to view their domains.
        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([DomainVoter::VIEW], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::UPDATE], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::VIEW], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::UPDATE], $this->domain2));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain2));

        // Anonymous users are not allowed to access any domain.
        $this->container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([DomainVoter::VIEW], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::UPDATE], $this->domain1));
        $this->assertFalse($dm->isGranted([DomainVoter::DELETE], $this->domain1));
    }
}