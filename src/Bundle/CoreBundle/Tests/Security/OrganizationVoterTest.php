<?php

namespace UniteCMS\CoreBundle\Tests\Security;

use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Security\Voter\OrganizationVoter;
use UniteCMS\CoreBundle\Tests\SecurityVoterTestCase;

class OrganizationVoterTest extends SecurityVoterTestCase
{

    public function testCRUDActions()
    {

        $dm = $this->container->get('security.authorization_checker');

        // If the user is not part of the organization, nothing is allowed.
        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::VIEW], $this->org1));
        $this->assertFalse($dm->isGranted([OrganizationVoter::UPDATE], $this->org1));
        $this->assertFalse($dm->isGranted([OrganizationVoter::DELETE], $this->org1));

        $this->container->get('security.token_storage')->setToken($this->u['user']);
        $this->assertTrue($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::VIEW], $this->org1));
        $this->assertFalse($dm->isGranted([OrganizationVoter::UPDATE], $this->org1));
        $this->assertFalse($dm->isGranted([OrganizationVoter::DELETE], $this->org1));

        $this->container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::VIEW], $this->org1));
        $this->assertFalse($dm->isGranted([OrganizationVoter::UPDATE], $this->org1));
        $this->assertFalse($dm->isGranted([OrganizationVoter::DELETE], $this->org1));

        // Platform admin is allowed to preform all actions on an organization.
        $this->container->get('security.token_storage')->setToken($this->u['platform']);
        $this->assertTrue($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertTrue($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertTrue($dm->isGranted([OrganizationVoter::VIEW], $this->org1));
        $this->assertTrue($dm->isGranted([OrganizationVoter::UPDATE], $this->org1));
        $this->assertTrue($dm->isGranted([OrganizationVoter::DELETE], $this->org1));

        // If the user is part of the organization, actions are allowed depending on the organization member role.
        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertTrue($dm->isGranted([OrganizationVoter::VIEW], $this->org2));
        $this->assertTrue($dm->isGranted([OrganizationVoter::UPDATE], $this->org2));
        $this->assertTrue($dm->isGranted([OrganizationVoter::DELETE], $this->org2));

        $this->container->get('security.token_storage')->setToken($this->u['user']);
        $this->assertTrue($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::UPDATE], $this->org2));
        $this->assertFalse($dm->isGranted([OrganizationVoter::DELETE], $this->org2));
        $this->assertTrue($dm->isGranted([OrganizationVoter::VIEW], $this->org2));

        // Test non supported role should not have any access rights.
        $this->container->get('security.token_storage')->setToken($this->u['non_supported_role']);
        $this->assertFalse($dm->isGranted([OrganizationVoter::LIST], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::CREATE], Organization::class));
        $this->assertFalse($dm->isGranted([OrganizationVoter::UPDATE], $this->org2));
        $this->assertFalse($dm->isGranted([OrganizationVoter::DELETE], $this->org2));
        $this->assertFalse($dm->isGranted([OrganizationVoter::VIEW], $this->org2));
    }

}
