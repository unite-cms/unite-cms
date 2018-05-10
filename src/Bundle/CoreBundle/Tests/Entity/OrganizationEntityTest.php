<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;

class OrganizationEntityTest extends TestCase
{
    public function testSetUsersToOrganization()
    {
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');

        $user1 = new User();
        $user2 = new User();

        $org1Member = new OrganizationMember();
        $org1Member->setOrganization($org1);
        $user1->addOrganization($org1Member);

        $org2Member = new OrganizationMember();
        $org2Member->setOrganization($org1);
        $user2->addOrganization($org1Member);

        // add the 2 users to the organisation
        $org1->setMembers(
            [
                $org1Member,
                $org2Member,
            ]
        );

        // test if users where added
        $this->assertCount(2, $org1->getMembers());
    }

    public function testOrganizationMemberSingleRoleMethods()
    {
        $orgMember = new OrganizationMember();
        $orgMember->setRoles([Organization::ROLE_USER, Organization::ROLE_ADMINISTRATOR]);
        $this->assertEquals([Organization::ROLE_USER, Organization::ROLE_ADMINISTRATOR], $orgMember->getRoles());
        $this->assertEquals(Organization::ROLE_ADMINISTRATOR, $orgMember->getSingleRole());

        $orgMember->setSingleRole(Organization::ROLE_USER);
        $this->assertEquals([Organization::ROLE_USER], $orgMember->getRoles());
        $this->assertEquals(Organization::ROLE_USER, $orgMember->getSingleRole());

        $orgMember->setSingleRole(Organization::ROLE_ADMINISTRATOR);
        $this->assertEquals([Organization::ROLE_ADMINISTRATOR], $orgMember->getRoles());
        $this->assertEquals(Organization::ROLE_ADMINISTRATOR, $orgMember->getSingleRole());
    }
}