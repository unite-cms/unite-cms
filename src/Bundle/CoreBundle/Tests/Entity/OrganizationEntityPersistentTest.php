<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class OrganizationEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateOrganization()
    {

        $org1 = new Organization();

        // Try to validate empty Organization.
        $org1->setTitle('')->setIdentifier('');
        $errors = $this->container->get('validator')->validate($org1);
        $this->assertCount(2, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        // Try to validate organization with too long title and identifier.
        $org1->setTitle($this->generateRandomUTF8String(256))->setIdentifier($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($org1);
        $this->assertCount(2, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        // Try to test invalid identifier.
        $org1
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier($this->generateRandomMachineName(254).':');
        $errors = $this->container->get('validator')->validate($org1);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Try to validate valid organization.
        $org1->setIdentifier($this->generateRandomMachineName(255));
        $this->assertCount(0, $this->container->get('validator')->validate($org1));

        // Save the organization to the database.
        $this->em->persist($org1);
        $this->em->flush($org1);

        // Try validate organization with the same identifier.
        $org2 = new Organization();
        $org2->setTitle($org1->getTitle())->setIdentifier($org1->getIdentifier());
        $errors = $this->container->get('validator')->validate($org2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.identifier_already_taken', $errors->get(0)->getMessage());

        // Try to add an invalid Domain to the organization.
        $org1->addDomain(new Domain());

        $errors = $this->container->get('validator')->validate($org1);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('domains', $errors->get(0)->getPropertyPath());

        // Try to delete non empty organization.
        $org1->addDomain(new Domain());

        $errors = $this->container->get('validator')->validate($org1, null, ['DELETE']);
        $this->assertCount(1, $errors);
        $this->assertEquals('domains', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.should_be_empty', $errors->get(0)->getMessage());
    }

    public function testAddUserToOrganization()
    {
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');
        $org2 = new Organization();
        $org2->setTitle('Org2')->setIdentifier('org2');

        $user1 = new User();
        $user1->setEmail('user1d@example.com')->setName('User 1')->setPassword('XXX');
        $this->assertCount(0, $user1->getOrganizations());
        $org1Member = new OrganizationMember();
        $org1Member->setOrganization($org1);
        $user1->addOrganization($org1Member);

        $this->assertCount(1, $user1->getOrganizations());
        $this->assertCount(0, $this->container->get('validator')->validate($user1));

        $this->em->persist($org1);
        $this->em->persist($org2);
        $this->em->persist($user1);
        $this->em->flush($user1);

        // A user cannot be member of the same organizations twice.
        $org2Member = new OrganizationMember();
        $org2Member->setOrganization($org1);
        $user1->addOrganization($org2Member);
        $this->assertCount(2, $user1->getOrganizations());
        $errors = $this->container->get('validator')->validate($user1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('organization', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.user_already_member_of_organization', $errors->get(0)->getMessage());

        // A user can be member of multiple organizations.
        $org2Member2 = new OrganizationMember();
        $org2Member2->setOrganization($org2);
        $user1->setOrganizations([$org1Member, $org2Member2]);
        $this->assertCount(2, $user1->getOrganizations());
        $this->assertCount(0, $this->container->get('validator')->validate($user1));
    }

    public function testSetDomainsToOrganization()
    {
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');

        $domain1 = new Domain();
        $domain1->setTitle('Domain1')
            ->setIdentifier('domain1');

        $domain2 = new Domain();
        $domain2->setTitle('Domain2')
            ->setIdentifier('domain2');

        $this->em->persist($org1);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->flush($domain1);
        $this->em->flush($domain2);

        // add the 2 domains to the organisation
        $org1->setDomains(
            [
                $domain1,
                $domain2,
            ]
        );

        // test if those 2 domains were added
        $this->assertCount(2, $org1->getDomains());
    }

    public function testAllowedOrganizationMemberRoles()
    {
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');

        $user1 = new User();
        $user1->setEmail('user1d@example.com')->setName('User 1')->setPassword('XXX');
        $org1Member = new OrganizationMember();
        $org1Member->setOrganization($org1);
        $user1->addOrganization($org1Member);

        $org1Member->setRoles(['UNKNOWN_ROLE']);
        $errors = $this->container->get('validator')->validate($user1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('organization', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_selection', $errors->get(0)->getMessage());

        $org1Member->setRoles([]);
        $errors = $this->container->get('validator')->validate($user1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('organization', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $org1Member->setRoles([Organization::ROLE_USER]);
        $this->assertCount(0, $this->container->get('validator')->validate($user1));

        $org1Member->setRoles([Organization::ROLE_ADMINISTRATOR]);
        $this->assertCount(0, $this->container->get('validator')->validate($user1));
    }

    public function testReservedIdentifiers()
    {
        $reserved = Organization::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $org = new Organization();
        $org->setTitle('title')->setIdentifier(array_pop($reserved));
        $errors = $this->container->get('validator')->validate($org);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());
    }

    public function testOrganizationMemberDeleteValidation() {

        $validator = $this->container->get('validator');

        // Updating or deleting organization member is only allowed if after that action there is at least one admin remaining.
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');

        $user1 = new User();
        $user1->setName('User 1')->setEmail('user1@example.com')->setPassword('XXX');

        $user2 = new User();
        $user2->setName('User 2')->setEmail('user2@example.com')->setPassword('XXX');

        $this->em->persist($org);
        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->flush();
        $this->em->refresh($org);

        // Adding users to an organization is valid at any time.
        $orgMember1 = new OrganizationMember();
        $orgMember1->setOrganization($org)->setUser($user1)->setSingleRole(Organization::ROLE_ADMINISTRATOR);
        $this->assertCount(0, $validator->validate($orgMember1, null, ['CREATE']));

        $this->em->persist($orgMember1);
        $this->em->flush($orgMember1);
        $this->em->refresh($org);

        $orgMember2 = new OrganizationMember();
        $orgMember2->setOrganization($org)->setUser($user2)->setSingleRole(Organization::ROLE_ADMINISTRATOR);
        $this->assertCount(0, $validator->validate($orgMember2, null, ['CREATE']));

        $this->em->persist($orgMember2);
        $this->em->flush($orgMember2);
        $this->em->refresh($org);

        // Deleting the first user should be valid, because there is still another admin in this org.
        $this->assertCount(0, $validator->validate($orgMember1, null, ['DELETE']));
        $this->em->remove($orgMember1);
        $this->em->flush($orgMember1);
        $this->em->refresh($org);

        // Deleting the 2nd user should not be valid, because no admin would be left.
        $violations = $validator->validate($orgMember2, null, ['DELETE']);
        $this->assertCount(1, $violations);
        $this->assertEquals('organization', $violations->get(0)->getPropertyPath());
        $this->assertEquals('validation.no_organization_admins', $violations->get(0)->getMessage());


        // Adding the first user again as user should work.
        $orgMember1User = new OrganizationMember();
        $orgMember1User->setOrganization($org)->setUser($user1)->setSingleRole(Organization::ROLE_USER);
        $this->assertCount(0, $validator->validate($orgMember1, null, ['CREATE']));

        $this->em->persist($orgMember1User);
        $this->em->flush();
        $this->em->refresh($org);

        // Deleting the 2nd user should still not be valid, because user1 is only a member and not an admin.
        $violations = $validator->validate($orgMember2, null, ['DELETE']);
        $this->assertCount(1, $violations);
        $this->assertEquals('organization', $violations->get(0)->getPropertyPath());
        $this->assertEquals('validation.no_organization_admins', $violations->get(0)->getMessage());

        // Updating the user role of user 2 should not work, because there would be no admin left.
        $orgMember2->setSingleRole(Organization::ROLE_USER);
        $violations = $validator->validate($orgMember2, null, ['UPDATE']);
        $this->assertCount(1, $violations);
        $this->assertEquals('organization', $violations->get(0)->getPropertyPath());
        $this->assertEquals('validation.no_organization_admins', $violations->get(0)->getMessage());

        // Making the first user an admin, should allow the 2nds user to change role.
        $orgMember1User->setSingleRole(Organization::ROLE_ADMINISTRATOR);
        $this->em->flush($orgMember1User);
        $this->em->refresh($org);

        $this->assertCount(0, $validator->validate($orgMember2, null, ['UPDATE']));
        $this->em->flush($orgMember2);
        $this->em->refresh($org);

        // Deleting the underlying user for the admin membership should not work.
        $violations = $validator->validate($user1, null, ['DELETE']);
        $this->assertCount(1, $violations);
        $this->assertEquals('organizations[0].organization', $violations->get(0)->getPropertyPath());
        $this->assertEquals('validation.no_organization_admins', $violations->get(0)->getMessage());

        // If user2 is admin again, we can now delete user1.
        $orgMember2->setSingleRole(Organization::ROLE_ADMINISTRATOR);
        $this->em->flush($orgMember2);
        $this->em->refresh($org);

        $this->assertCount(0, $validator->validate($user1, null, ['DELETE']));
        $this->em->remove($user1);
        $this->em->flush();
        $this->em->refresh($org);

        $violations = $validator->validate($orgMember2, null, ['DELETE']);
        $this->assertCount(1, $violations);
        $this->assertEquals('organization', $violations->get(0)->getPropertyPath());
        $this->assertEquals('validation.no_organization_admins', $violations->get(0)->getMessage());
    }
}
