<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Role\Role;

class UserEntityTest extends TestCase
{
    public function testSetDomainsToUser()
    {
        $user1 = new User();

        // Add user to domain of org 1.
        $member1 = new DomainMember();
        $member1->setDomain(new Domain());
        $user1->addDomain($member1);

        // Add user to domain of org 1.
        $member2 = new DomainMember();
        $member2->setDomain(new Domain());
        $user1->addDomain($member2);

        $user1->setDomains(
            [
                $member1,
                $member2,
            ]
        );

        $this->assertCount(2, $user1->getDomains());
    }

    public function testExistingRole()
    {
        $user = new User();
        $role = new Role('ROLE_USER');

        $this->assertContains('ROLE_USER', $user->getRoles());

        $ret_object = $user->setRoles(
            [
                $role,
            ]
        );

        $this->assertEquals($user, $ret_object);
    }

    public function testUserSetAndGetOrganizations()
    {
        $org1 = new Organization();
        $org2 = new Organization();
        $org3 = new Organization();

        $organizationMember1 = new OrganizationMember();
        $organizationMember1->setOrganization($org1);

        $organizationMember2 = new OrganizationMember();
        $organizationMember2->setOrganization($org2);

        $organizationMember3 = new OrganizationMember();
        $organizationMember3->setOrganization($org3);

        $user = new User();
        $user->addOrganization($organizationMember1)
            ->addOrganization($organizationMember2);

        // check a valid domain
        $this->assertCount(1, $user->getOrganizationRoles($org2));

        // check an invalid domain
        $this->assertCount(0, $user->getOrganizationRoles($org3));
    }
}