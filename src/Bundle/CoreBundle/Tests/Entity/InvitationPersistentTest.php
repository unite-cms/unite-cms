<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class InvitationPersistentTest extends DatabaseAwareTestCase
{
    public function testValidateInvitation()
    {
        $org = new Organization();
        $org->setIdentifier('org')->setTitle('org');

        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');

        $domain12 = new Domain();
        $domain12->setOrganization($org)->setTitle('Domain12')->setIdentifier('domain12');

        $org2 = new Organization();
        $org2->setTitle('org2')->setIdentifier('Org2');
        $domain2 = new Domain();
        $domain2->setOrganization($org2)->setTitle('Domain2')->setIdentifier('domain2');

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->persist($domain12);
        $this->em->persist($org2);
        $this->em->persist($domain2);

        $this->em->flush();

        //Validate empty invite.
        $invite1 = new Invitation();
        $errors = static::$container->get('validator')->validate($invite1);
        $this->assertCount(4, $errors);
        $this->assertStringStartsWith('organization', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());
        $this->assertStringStartsWith('email', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());
        $this->assertStringStartsWith('token', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());
        $this->assertStringStartsWith('requestedAt', $errors->get(3)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(3)->getMessageTemplate());


        //Validate invalid email
        $invite1->setOrganization($org);
        $invite1->setEmail('XXX')->setToken('XXX')->setRequestedAt(new \DateTime());
        $errors = static::$container->get('validator')->validate($invite1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_email', $errors->get(0)->getMessageTemplate());

        // Validate too long token.
        $invite1->setToken($this->generateRandomMachineName(181));

        $errors = [];

        foreach (static::$container->get('validator')->validate($invite1) as $error) {
            $errors[$error->getPropertyPath()] = $error->getMessageTemplate();
        }

        $this->assertArrayHasKey('token', $errors);
        $this->assertEquals($errors['token'], 'too_long');

        // Validate invalid token characters.
        $invite1->setToken('   '.$this->generateRandomUTF8String(150));

        $errors = [];

        foreach (static::$container->get('validator')->validate($invite1) as $error) {
            $errors[$error->getPropertyPath()] = $error->getMessageTemplate();
        }

        $this->assertArrayHasKey('token', $errors);
        $this->assertEquals($errors['token'], 'invalid_characters');

        $invite1->setToken('XXX');

        // Validate valid invite
        $invite1->setEmail('user1@example.com');
        $this->assertCount(0, static::$container->get('validator')->validate($invite1));

        // Validate valid invite with invalid domain member type
        $invite1->setDomainMemberType($domain2->getDomainMemberTypes()->first());
        $errors = static::$container->get('validator')->validate($invite1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('domainMemberType', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_domain_member_type', $errors->get(0)->getMessageTemplate());

        $this->em->persist($domain);
        $this->em->persist($invite1);
        $this->em->flush($invite1);

        // Validate invite uniqueness
        $invite2 = new Invitation();

        $invite2->setOrganization($org);
        $invite2->setEmail('user1@example.com');
        $invite2->setToken('XXX')->setRequestedAt(new \DateTime());
        $errors = static::$container->get('validator')->validate($invite2);
        $this->assertCount(2, $errors);
        $this->assertStringStartsWith('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('email_already_invited', $errors->get(0)->getMessageTemplate());
        $this->assertStringStartsWith('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('token_already_present', $errors->get(1)->getMessageTemplate());

        $invite2->setDomainMemberType($domain12->getDomainMemberTypes()->first());
        $errors = static::$container->get('validator')->validate($invite2);
        $this->assertCount(2, $errors);
        $this->assertStringStartsWith('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('email_already_invited', $errors->get(0)->getMessageTemplate());
        $this->assertStringStartsWith('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('token_already_present', $errors->get(1)->getMessageTemplate());

        $invite2->setDomainMemberType(null);

        $invite2->setOrganization($org2);
        $invite2->setEmail('user1@example.com')->setToken('YYY');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        $invite2->setOrganization($org2);
        $invite2->setEmail('user1@example.com')->setToken('YYY');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        $invite2->setOrganization($org);
        $invite2->setEmail('user2@example.com');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        $invite2->setOrganization($org2);
        $invite2->setEmail('user2@example.com');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        // Validate invite email cannot be the email address of a member of this organization.
        $user1 = new User();
        $user1->setPassword('XXX')->setName('XXX')->setEmail('org1user@example.com');
        $org1Member = new OrganizationMember();
        $org1Member->setUser($user1);
        $org->addMember($org1Member);

        $invite2->setEmail('org1user@example.com');
        $invite2->setOrganization($org);
        $errors = static::$container->get('validator')->validate($invite2);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('email_already_member', $errors->get(0)->getMessageTemplate());
    }
}
