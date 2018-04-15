<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class UserEntityTest extends DatabaseAwareTestCase
{

    public function testValidateUserOnCreate()
    {
        $user = new User();
        $user->setLastname('')->setFirstname('')->setEmail('')->setPassword('');
        $errors = $this->container->get('validator')->validate($user, null, ['User', 'CREATE']);
        $this->assertCount(4, $errors);

        $this->assertEquals('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('firstname', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('lastname', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        $this->assertEquals('password', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(3)->getMessage());
    }

    public function testValidateFieldLength()
    {
        $user = new User();
        $org = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org);
        $org->setIdentifier('org1')->setTitle('Org1');
        $user->addOrganization($organizationMember);
        $user->setLastname($this->generateRandomUTF8String(256))->setFirstname(
            $this->generateRandomUTF8String(256)
        )->setEmail($this->generateRandomMachineName(256).'@example.com')->setPassword(
            $this->generateRandomUTF8String(256)
        );
        $errors = $this->container->get('validator')->validate($user);
        $this->assertCount(4, $errors);

        $this->assertEquals('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('firstname', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        $this->assertEquals('lastname', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(2)->getMessage());

        $this->assertEquals('password', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(3)->getMessage());
    }

    public function testValidateEmail()
    {
        $user = new User();
        $org = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org);
        $org->setIdentifier('org1')->setTitle('Org1');
        $user->addOrganization($organizationMember);
        $user->setLastname($this->generateRandomUTF8String(255))->setFirstname(
            $this->generateRandomUTF8String(255)
        )->setEmail('invalid@invalid@invalid')->setPassword($this->generateRandomUTF8String(255));
        $errors = $this->container->get('validator')->validate($user);
        $this->assertCount(1, $errors);

        $this->assertEquals('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_email', $errors->get(0)->getMessage());
    }

    public function testValidateUserOnUpdate()
    {
        $user = new User();
        $errors = $this->container->get('validator')->validate($user);
        $this->assertCount(3, $errors);

        $this->assertEquals('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('firstname', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('lastname', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());
    }

    public function testValidateUniqueUserEntity()
    {
        $org = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org);
        $org->setIdentifier('org1')->setTitle('Org1');
        $org2 = new Organization();
        $organizationMember2 = new OrganizationMember();
        $organizationMember2->setOrganization($org2);
        $org2->setIdentifier('org2')->setTitle('Org2');
        $user1 = new User();
        $user1->setLastname('1')->setFirstname('User')->setEmail('user1@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember);

        $this->em->persist($org);
        $this->em->persist($org2);
        $this->em->persist($user1);
        $this->em->flush($user1);

        $user2 = new User();
        $user2->setLastname('1')->setFirstname('User')->setEmail('user1@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember2);
        $errors = $this->container->get('validator')->validate($user2);
        $this->assertCount(1, $errors);
        $this->assertEquals('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.email_already_taken', $errors->get(0)->getMessage());

        $user3 = new User();
        $user3->setLastname('1')->setFirstname('User')->setEmail('user2@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember);
        $this->assertCount(0, $this->container->get('validator')->validate($user3));

        // Test Email already taken for different organizations.
        $user4 = new User();
        $user4->setLastname('1')->setFirstname('User')->setEmail('user1@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember2);
        $errors = $this->container->get('validator')->validate($user4);
        $this->assertCount(1, $errors);
        $this->assertEquals('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.email_already_taken', $errors->get(0)->getMessage());
    }

    public function testDeleteOrganizationShouldNotDeleteUsers()
    {

        $org = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org);
        $org->setIdentifier('org1')->setTitle('Org 1');
        $user1 = new User();
        $user1->setLastname('1')->setFirstname('User')->setEmail('user1@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember);

        $this->em->persist($org);
        $this->em->persist($user1);
        $this->em->flush($org);
        $this->em->flush($user1);

        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:User')->findAll());
        $this->em->remove($org);
        $this->em->flush();
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:User')->findAll());
    }

    public function testDefaultUserRole()
    {
        $user = new User();
        $this->assertContains('ROLE_USER', $user->getRoles());
        $user->setRoles([]);
        $this->assertContains('ROLE_USER', $user->getRoles());
        $user->setRoles(['ROLE_EDITOR']);
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_EDITOR', $user->getRoles());
        $user->setRoles(['ROLE_USER']);
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserDomainMustBeInSameOrganization()
    {

        $org1 = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org1);
        $org1->setIdentifier('org1')->setTitle('Org 1');
        $org2 = new Organization();
        $org2->setIdentifier('org2')->setTitle('Org 2');

        $domain1 = new Domain();
        $domain1->setOrganization($org1)->setTitle('Domain1')->setIdentifier('domain1');

        $domain2 = new Domain();
        $domain2->setOrganization($org2)->setTitle('Domain2')->setIdentifier('domain2');

        $this->em->persist($org1);
        $this->em->persist($org2);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->flush();
        $this->em->refresh($org1);
        $this->em->refresh($org2);

        $user = new User();
        $user->setLastname('1')->setFirstname('User')->setEmail('user1@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember);

        // Add user to domain of org 1.
        $member = new DomainMember();
        $member->setDomain($domain1);
        $user->addDomain($member);
        $this->assertCount(0, $this->container->get('validator')->validate($user));

        // Add user to domain of org 2.
        $member2 = new DomainMember();
        $member2->setDomain($domain2);
        $user->addDomain($member2);
        $errors = $this->container->get('validator')->validate($user);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('domains', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.domain_organization', $errors->get(0)->getMessage());
    }

    public function testTokenValidation()
    {

        $user = new User();

        // Validate too long token.
        $user->setResetToken($this->generateRandomMachineName(181));

        $errors = [];

        foreach ($this->container->get('validator')->validate($user) as $error) {
            $errors[$error->getPropertyPath()] = $error->getMessage();
        }

        $this->assertArrayHasKey('resetToken', $errors);
        $this->assertEquals($errors['resetToken'], 'validation.too_long');


        // Validate invalid token characters.
        $user->setResetToken('   '.$this->generateRandomUTF8String(150));

        $errors = [];

        foreach ($this->container->get('validator')->validate($user) as $error) {
            $errors[$error->getPropertyPath()] = $error->getMessage();
        }

        $this->assertArrayHasKey('resetToken', $errors);
        $this->assertEquals($errors['resetToken'], 'validation.invalid_characters');

        // Validate token uniqueness.
        $user
            ->setResetToken($this->generateRandomMachineName(150))
            ->setRoles([User::ROLE_USER])
            ->setPassword('password')
            ->setEmail('user@example.com')
            ->setFirstname('User1')
            ->setLastname('User1');

        $this->assertCount(0, $this->container->get('validator')->validate($user));
        $this->em->persist($user);
        $this->em->flush($user);

        $user2 = new User();
        $user2->setResetToken($user->getResetToken());

        $errors = [];

        foreach ($this->container->get('validator')->validate($user2) as $error) {
            $errors[$error->getPropertyPath()] = $error->getMessage();
        }

        $this->assertArrayHasKey('resetToken', $errors);
        $this->assertEquals($errors['resetToken'], 'validation.reset_token_present');
    }
}
