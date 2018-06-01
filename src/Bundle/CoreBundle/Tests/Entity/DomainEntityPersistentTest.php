<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainInvitation;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class DomainEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateDomain()
    {

        $domain1 = new Domain();

        // Try to validate empty Domain.
        $domain1->setTitle('')->setIdentifier('')->setPermissions([]);
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertCount(3, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());

        $this->assertEquals('organization', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());

        // Try to validate organization with too long title and identifier.
        $domain1
            ->setTitle($this->generateRandomUTF8String(256))
            ->setIdentifier($this->generateRandomMachineName(256));
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertGreaterThanOrEqual(2, $errors->count());

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(1)->getMessageTemplate());

        // Try to test invalid identifier.
        $domain1
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier($this->generateRandomMachineName(254).':');
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertGreaterThanOrEqual(1, $errors->count());

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        // Try to set invalid ContentType.
        $org = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org);
        $domainMember = new DomainMember();
        $domainMember->setDomainMemberType($domain1->getDomainMemberTypes()->first())->setAccessor(new User())->getAccessor()->addOrganization($organizationMember)->setEmail(
            'example@example.com'
        );
        $domain1
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier($this->generateRandomMachineName(255))
            ->setOrganization($org)
            ->addContentType(new ContentType())
            ->addSettingType(new SettingType())
            ->addMember($domainMember);

        $domain1->getOrganization()->setMembers([]);
        $domain1->getOrganization()->setIdentifier('domain_org1')->setTitle('Domain Org 1');
        $domain1->getContentTypes()->first()->setPermissions(
            [
                ContentVoter::VIEW => 'true',
                ContentVoter::LIST => 'true',
                ContentVoter::CREATE => 'true',
                ContentVoter::UPDATE => 'true',
                ContentVoter::DELETE => 'true',
            ]
        );

        // Try to set invalid SettingType
        $domain1->getSettingTypes()->first()->setPermissions(
            [
                SettingVoter::VIEW => 'true',
                SettingVoter::UPDATE => 'true',
            ]
        );

        // Try to set invalid DomainMemberType
        $domain1->getDomainMemberTypes()->first()->setIcon('ä#;');

        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('contentTypes', $errors->get(0)->getPropertyPath());

        // Try to test invalid SettingType.
        $domain1->getContentTypes()->first()->setIdentifier('domain_ct1')->setTitle('Domain CT 1');
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('settingTypes', $errors->get(0)->getPropertyPath());

        // Try to test invalid MemberType.
        $domain1->getSettingTypes()->first()->setIdentifier('domain_set1')->setTitle('Domain Set 1');
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('domainMemberTypes', $errors->get(0)->getPropertyPath());

        // Try to test invalid users.
        $domain1->getDomainMemberTypes()->first()->setIcon('');
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertGreaterThanOrEqual(1, $errors->count());
        $this->assertStringStartsWith('members', $errors->get(0)->getPropertyPath());

        // Test valid Domain.
        $user = new User();
        $org = new Organization();
        $organizationMember = new OrganizationMember();
        $organizationMember->setOrganization($org);
        $org->setIdentifier('org1')->setTitle('Org1');
        $user->setName('User 1')->setEmail('user1@example.com')->setPassword(
            'password'
        )->addOrganization($organizationMember);
        $domainMember = new DomainMember();
        $domainMember->setAccessor($user)->setDomainMemberType($domain1->getDomainMemberTypes()->first());
        $domain1->setMembers([$domainMember]);
        $domain1->setOrganization($org);
        $errors = static::$container->get('validator')->validate($domain1);
        $this->assertCount(0, $errors);

        // Persist the domain.
        $domain1->setMembers([])->setContentTypes([])->setSettingTypes([]);
        $domain1->getDomainMemberTypes()->first()
            ->setIcon('')
            ->setIdentifier('users')
            ->setTitle('users');
        $this->em->persist($domain1->getOrganization());
        $this->em->persist($domain1);
        $this->em->flush($domain1);
        $this->em->flush($domain1->getOrganization());

        // Try validate domain with the same identifier and the same organization.
        $domain2 = new Domain();
        $domain2->setTitle($domain1->getTitle())->setIdentifier($domain1->getIdentifier())->setOrganization(
            $domain1->getOrganization()
        );
        $errors = static::$container->get('validator')->validate($domain2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('identifier_already_taken', $errors->get(0)->getMessageTemplate());

        // Test all combinations of same organizations and identifier
        $org2 = new Organization();
        $org2->setTitle('Domain Org 1')->setIdentifier('domain_org2');
        $this->em->persist($org2);
        $this->em->flush($org2);

        // Same organization, different identifier should be valid
        $domain2->setIdentifier('different_domain_identifier_2');
        $this->assertCount(0, static::$container->get('validator')->validate($domain2));

        // Different organization, same identifier should be valid
        $domain2->setIdentifier($domain1->getIdentifier())->setOrganization($org2);
        $this->assertCount(0, static::$container->get('validator')->validate($domain2));

        // Different organization, different identifier should be valid
        $domain2->setIdentifier('different_domain_identifier_2')->setOrganization($org2);
        $this->assertCount(0, static::$container->get('validator')->validate($domain2));

        // Try to delete non empty domain.
        $ct = new ContentType();
        $ct->setTitle('New Content Type')->setIdentifier('domain_entity_test_new_ct')->setWeight(0);
        $content = new Content();
        $content->setContentType($ct);
        $this->em->persist($ct);
        $this->em->persist($content);
        $this->em->flush($ct);
        $this->em->flush($content);
        $this->em->refresh($ct);

        $domain2
            ->addContentType($ct);

        // Normal validation should be fine.
        $this->assertCount(0, static::$container->get('validator')->validate($domain2));

        // We allow to delete domains with content_types. They also will get deleted.
        $this->assertCount(0, static::$container->get('validator')->validate($domain2, null, ['DELETE']));

        // try to validate invalid content
        $content->setData(['any_unknown_field' => 'foo']);
        $errors = static::$container->get('validator')->validate($domain2);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('content', $errors->get(0)->getPropertyPath());
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // Empty domains can be deleted.
        $this->em->remove($content);
        $this->em->flush($content);
        $this->em->refresh($ct);
        $this->assertCount(0, static::$container->get('validator')->validate($domain2, null, ['DELETE']));
    }

    private function setUpOriginDomain()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');
        $st1 = new SettingType();
        $st1->setIdentifier('st1')->setTitle('St1');
        $st1->setDomain($domain);
        $st2 = new SettingType();
        $st2->setIdentifier('st2')->setTitle('St2');
        $st2->setDomain($domain);

        $ct1 = new ContentType();
        $ct1->setIdentifier('ct1')->setTitle('Ct1');
        $ct1->setDomain($domain);
        $ct2 = new ContentType();
        $ct2->setIdentifier('ct2')->setTitle('Ct2');
        $ct2->setDomain($domain);

        $dmt1 = new DomainMemberType();
        $dmt1->setIdentifier('dmt1')->setTitle('Dmt1');
        $dmt1->setDomain($domain);
        $dmt2 = new DomainMemberType();
        $dmt2->setIdentifier('dmt2')->setTitle('Dmt2');
        $dmt2->setDomain($domain);

        $field1 = new ContentTypeField();
        $field1->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($ct1);
        $field2 = new ContentTypeField();
        $field2->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($ct2);
        $field3 = new ContentTypeField();
        $field3->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($ct2);
        $field4 = new ContentTypeField();
        $field4->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($ct1);

        $field11 = new SettingTypeField();
        $field11->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($st1);
        $field12 = new SettingTypeField();
        $field12->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($st2);
        $field13 = new SettingTypeField();
        $field13->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($st2);
        $field14 = new SettingTypeField();
        $field14->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($st1);

        $fieldd1 = new DomainMemberTypeField();
        $fieldd1->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($dmt1);
        $fieldd2 = new DomainMemberTypeField();
        $fieldd2->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($dmt2);
        $fieldd3 = new DomainMemberTypeField();
        $fieldd3->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($dmt2);
        $fieldd4 = new DomainMemberTypeField();
        $fieldd4->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($dmt1);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($domain);

        $content1 = new Content();
        $content1->setEntity($ct1);
        $setting1 = new Setting();
        $setting1->setEntity($st1);

        $this->em->persist($content1);
        $this->em->persist($setting1);
        $this->em->flush();
        $this->em->refresh($content1);
        $this->em->refresh($setting1);
        $this->em->refresh($domain);

        return $domain;
    }

    public function testDomainInOrganizationUser()
    {

        $user1 = new User();
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');
        $domain1 = new Domain();
        $domain1->setTitle('Domain1')
            ->setIdentifier('domain2')
            ->setOrganization($org1);
        $user1->setEmail('user1d@example.com')
            ->setName('User 1')
            ->setPassword('XXX');

        // A user can only be member of a domain, if he_she is member of the organization.
        $user1MemberDomain1 = new DomainMember();
        $user1MemberDomain1->setDomain($domain1)->setDomainMemberType($domain1->getDomainMemberTypes()->first());
        $user1->addDomain($user1MemberDomain1);

        $errors = static::$container->get('validator')->validate($user1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith(
            'domain',
            $errors->get(0)
                ->getPropertyPath()
        );
        $this->assertEquals(
            'domain_organization',
            $errors->get(0)
                ->getMessageTemplate()
        );

        $user1MemberOrg1 = new OrganizationMember();
        $user1MemberOrg1->setOrganization($org1);
        $user1->addOrganization($user1MemberOrg1);

        $this->assertCount(
            0,
            static::$container->get('validator')
                ->validate($user1)
        );
    }

    public function testUniqueDomainOrganizationUser()
    {

        $user1 = new User();
        $org1 = new Organization();
        $org1->setTitle('Org1')->setIdentifier('org1');
        $domain1 = new Domain();
        $domain1->setTitle('Domain1')
            ->setIdentifier('domain2')
            ->setOrganization($org1);
        $user1->setEmail('user1d@example.com')
            ->setName('User 1')
            ->setPassword('XXX');

        // A user cannot be member of the same organization twice.
        $user1MemberOrg1 = new OrganizationMember();
        $user1MemberOrg1->setOrganization($org1);
        $user1->addOrganization($user1MemberOrg1);

        $this->em->persist($org1);
        $this->em->persist($domain1);
        $this->em->persist($user1);
        $this->em->flush($user1);

        $this->assertCount(0, static::$container->get('validator')->validate($user1));

        $user1MemberOrg2 = new OrganizationMember();
        $user1MemberOrg2->setOrganization($org1);
        $user1->addOrganization($user1MemberOrg2);

        $errors = static::$container->get('validator')->validate($user1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith(
            'organization',
            $errors->get(0)
                ->getPropertyPath()
        );
        $this->assertEquals(
            'user_already_member_of_organization',
            $errors->get(0)
                ->getMessageTemplate()
        );


        // A user cannot be member of the same domain and type twice.
        $user1->setOrganizations([$user1MemberOrg1]);
        $user1MemberDomain1 = new DomainMember();
        $user1MemberDomain1->setDomain($domain1)->setDomainMemberType($domain1->getDomainMemberTypes()->first());
        $user1->addDomain($user1MemberDomain1);

        $this->em->flush($user1);

        $this->assertCount(0, static::$container->get('validator')->validate($user1));

        $user1MemberDomain2 = new DomainMember();
        $user1MemberDomain2->setDomain($domain1)->setDomainMemberType($domain1->getDomainMemberTypes()->first());
        $user1->addDomain($user1MemberDomain2);

        $errors = static::$container->get('validator')->validate($user1);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith(
            'domain',
            $errors->get(0)
                ->getPropertyPath()
        );
        $this->assertEquals(
            'user_already_member_of_domain_for_type',
            $errors->get(0)
                ->getMessageTemplate()
        );

        $domainMemberType2 = new DomainMemberType();
        $domainMemberType2->setTitle('DMT2')->setIdentifier('dmt2');
        $domain1->addDomainMemberType($domainMemberType2);
        $user1MemberDomain2->setDomainMemberType($domainMemberType2);

        echo static::$container->get('validator')->validate($user1);
        $this->assertCount(0, static::$container->get('validator')->validate($user1));
    }

    // Case 1: Domain have additional ContentTypes, SettingTypes and DomainMemberTypes
    public function testSetIdsFromOriginWithMoreContentTypes()
    {

        $domain = $this->setUpOriginDomain();
        $updateDomain = new Domain();
        $updateDomain->setOrganization($domain->getOrganization());
        $updateDomain->setDomainMemberTypes([]);
        $updateDomain->setIdentifier('domain')->setTitle('New Title');
        for ($i = 1; $i <= 3; $i++) {
            $ct = new ContentType();
            $ct->setIdentifier('ct'.$i)->setTitle('Ct'.$i);
            $ct->setDomain($updateDomain);
            $st = new SettingType();
            $st->setIdentifier('st'.$i)->setTitle('St'.$i);
            $st->setDomain($updateDomain);
            $dmt = new DomainMemberType();
            $dmt->setIdentifier('dmt'.$i)->setTitle('Dmt'.$i);
            $dmt->setDomain($updateDomain);
        }

        $domainIds = (object)[
            'ct1' => $domain->getContentTypes()->get('ct1')->getId(),
            'ct2' => $domain->getContentTypes()->get('ct2')->getId(),
            'st1' => $domain->getSettingTypes()->get('st1')->getId(),
            'st2' => $domain->getSettingTypes()->get('st2')->getId(),
            'dmt1' => $domain->getDomainMemberTypes()->get('dmt1')->getId(),
            'dmt2' => $domain->getDomainMemberTypes()->get('dmt2')->getId(),
            'ct1f1' => $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId(),
            'ct1f2' => $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId(),
            'st1f1' => $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId(),
            'st1f2' => $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId(),
            'dmt1f1' => $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId(),
            'dmt1f2' => $domain->getDomainMemberTypes()->get('dmt2')->getFields()->get('f2')->getId(),
        ];

        $domain->setFromEntity($updateDomain);
        $this->assertCount(0, static::$container->get('validator')->validate($domain));
        $this->em->flush($domain);
        $domain = $this->em->find('UniteCMSCoreBundle:Domain', $domain->getId());
        $this->assertCount(3, $domain->getContentTypes());
        $this->assertCount(3, $domain->getSettingTypes());
        $this->assertEquals($domainIds->ct1, $domain->getContentTypes()->get('ct1')->getId());
        $this->assertEquals($domainIds->ct2, $domain->getContentTypes()->get('ct2')->getId());
        $this->assertEquals($domainIds->st1, $domain->getSettingTypes()->get('st1')->getId());
        $this->assertEquals($domainIds->st2, $domain->getSettingTypes()->get('st2')->getId());
        $this->assertEquals($domainIds->dmt1, $domain->getDomainMemberTypes()->get('dmt1')->getId());
        $this->assertEquals($domainIds->dmt2, $domain->getDomainMemberTypes()->get('dmt2')->getId());
    }

    // Case 2: Domain have the same ContentTypes, SettingTypes and DomainMemberTypes
    public function testSetIdsFromOriginWithSameContentTypes()
    {

        $domain = $this->setUpOriginDomain();
        $updateDomain = new Domain();
        $updateDomain->setOrganization($domain->getOrganization());
        $updateDomain->setIdentifier('domain')->setTitle('New Title');
        for ($i = 1; $i <= 2; $i++) {
            $ct = new ContentType();
            $ct->setIdentifier('ct'.$i)->setTitle('Ct'.$i);
            $ct->setDomain($updateDomain);
            $st = new SettingType();
            $st->setIdentifier('st'.$i)->setTitle('St'.$i);
            $st->setDomain($updateDomain);
            $dmt = new DomainMemberType();
            $dmt->setIdentifier('dmt'.$i)->setTitle('Dmt'.$i);
            $dmt->setDomain($updateDomain);
            for ($k = 1; $k <= 2; $k++) {
                $field1 = new ContentTypeField();
                $field1->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($ct);
                $field2 = new SettingTypeField();
                $field2->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($st);
                $field3 = new DomainMemberTypeField();
                $field3->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($dmt);
            }
        }

        $domainIds = (object)[
            'ct1' => $domain->getContentTypes()->get('ct1')->getId(),
            'ct2' => $domain->getContentTypes()->get('ct2')->getId(),
            'st1' => $domain->getSettingTypes()->get('st1')->getId(),
            'st2' => $domain->getSettingTypes()->get('st2')->getId(),
            'dmt1' => $domain->getDomainMemberTypes()->get('dmt1')->getId(),
            'dmt2' => $domain->getDomainMemberTypes()->get('dmt2')->getId(),
            'ct1f1' => $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId(),
            'ct1f2' => $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId(),
            'st1f1' => $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId(),
            'st1f2' => $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId(),
            'dmt1f1' => $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId(),
            'dmt1f2' => $domain->getDomainMemberTypes()->get('dmt2')->getFields()->get('f2')->getId(),
        ];


        $domain->setFromEntity($updateDomain);
        $this->assertCount(0, static::$container->get('validator')->validate($domain));
        $this->em->flush($domain);
        $domain = $this->em->find('UniteCMSCoreBundle:Domain', $domain->getId());

        $this->assertCount(2, $domain->getContentTypes());
        $this->assertCount(2, $domain->getSettingTypes());
        $this->assertEquals($domainIds->ct1, $domain->getContentTypes()->get('ct1')->getId());
        $this->assertEquals($domainIds->ct2, $domain->getContentTypes()->get('ct2')->getId());
        $this->assertEquals($domainIds->st1, $domain->getSettingTypes()->get('st1')->getId());
        $this->assertEquals($domainIds->st2, $domain->getSettingTypes()->get('st2')->getId());
        $this->assertEquals($domainIds->ct1f1, $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->ct1f2, $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId());
        $this->assertEquals($domainIds->st1f1, $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->st1f2, $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId());
    }

    // Case 3: Domain have the same ContentTypes, SettingTypes and DomainMemberTypes but additional fields
    public function testSetIdsFromOriginWithMoreFields()
    {
        $domain = $this->setUpOriginDomain();
        $updateDomain = new Domain();
        $updateDomain->setOrganization($domain->getOrganization());
        $updateDomain->setIdentifier('domain')->setTitle('New Title');
        for ($i = 1; $i <= 2; $i++) {
            $ct = new ContentType();
            $ct->setIdentifier('ct'.$i)->setTitle('Ct'.$i);
            $ct->setDomain($updateDomain);
            $st = new SettingType();
            $st->setIdentifier('st'.$i)->setTitle('St'.$i);
            $st->setDomain($updateDomain);
            $dmt = new DomainMemberType();
            $dmt->setIdentifier('dmt'.$i)->setTitle('Dmt'.$i);
            $dmt->setDomain($updateDomain);

            for ($k = 1; $k <= 3; $k++) {
                $field1 = new ContentTypeField();
                $field1->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($ct);
                $field2 = new SettingTypeField();
                $field2->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($st);
                $field3 = new DomainMemberTypeField();
                $field3->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($dmt);
            }
        }

        $domainIds = (object)[
            'ct1' => $domain->getContentTypes()->get('ct1')->getId(),
            'ct2' => $domain->getContentTypes()->get('ct2')->getId(),
            'st1' => $domain->getSettingTypes()->get('st1')->getId(),
            'st2' => $domain->getSettingTypes()->get('st2')->getId(),
            'dmt1' => $domain->getDomainMemberTypes()->get('dmt1')->getId(),
            'dmt2' => $domain->getDomainMemberTypes()->get('dmt2')->getId(),
            'ct1f1' => $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId(),
            'ct1f2' => $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId(),
            'st1f1' => $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId(),
            'st1f2' => $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId(),
            'dmt1f1' => $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId(),
            'dmt1f2' => $domain->getDomainMemberTypes()->get('dmt2')->getFields()->get('f2')->getId(),
        ];

        $domain->setFromEntity($updateDomain);
        $this->assertCount(0, static::$container->get('validator')->validate($domain));
        $this->em->flush($domain);
        $domain = $this->em->find('UniteCMSCoreBundle:Domain', $domain->getId());

        $this->assertCount(2, $domain->getContentTypes());
        $this->assertCount(2, $domain->getSettingTypes());
        $this->assertCount(3, $domain->getContentTypes()->get('ct1')->getFields());
        $this->assertCount(3, $domain->getContentTypes()->get('ct2')->getFields());
        $this->assertEquals($domainIds->ct1, $domain->getContentTypes()->get('ct1')->getId());
        $this->assertEquals($domainIds->ct2, $domain->getContentTypes()->get('ct2')->getId());
        $this->assertEquals($domainIds->st1, $domain->getSettingTypes()->get('st1')->getId());
        $this->assertEquals($domainIds->st2, $domain->getSettingTypes()->get('st2')->getId());
        $this->assertEquals($domainIds->dmt1, $domain->getDomainMemberTypes()->get('dmt1')->getId());
        $this->assertEquals($domainIds->dmt2, $domain->getDomainMemberTypes()->get('dmt2')->getId());
        $this->assertEquals($domainIds->ct1f1, $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->ct1f2, $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId());
        $this->assertEquals($domainIds->st1f1, $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->st1f2, $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId());
        $this->assertEquals($domainIds->dmt1f1, $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->dmt1f2, $domain->getDomainMemberTypes()->get('dmt2')->getFields()->get('f2')->getId());
    }

    // Case 4: Domain have the same ContentTypes, SettingTypes and DomainMemberTypes but less fields
    public function testSetIdsFromOriginWithLessFields()
    {
        $domain = $this->setUpOriginDomain();
        $updateDomain = new Domain();
        $updateDomain->setOrganization($domain->getOrganization());
        $updateDomain->setIdentifier('domain')->setTitle('New Title');
        $updateDomain->setDomainMemberTypes([]);
        for ($i = 1; $i <= 2; $i++) {
            $ct = new ContentType();
            $ct->setIdentifier('ct'.$i)->setTitle('Ct'.$i);
            $ct->setDomain($updateDomain);
            $st = new SettingType();
            $st->setIdentifier('st'.$i)->setTitle('St'.$i);
            $st->setDomain($updateDomain);
            $dmt = new DomainMemberType();
            $dmt->setIdentifier('dmt'.$i)->setTitle('Dmt'.$i);
            $dmt->setDomain($updateDomain);

            for ($k = 1; $k <= 1; $k++) {
                $field1 = new ContentTypeField();
                $field1->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($ct);
                $field2 = new SettingTypeField();
                $field2->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($st);
                $field3 = new DomainMemberTypeField();
                $field3->setTitle('F'.$k)->setIdentifier('f'.$k)->setType('text')->setEntity($dmt);
            }
        }

        $domainIds = (object)[
            'ct1' => $domain->getContentTypes()->get('ct1')->getId(),
            'ct2' => $domain->getContentTypes()->get('ct2')->getId(),
            'st1' => $domain->getSettingTypes()->get('st1')->getId(),
            'st2' => $domain->getSettingTypes()->get('st2')->getId(),
            'dmt1' => $domain->getDomainMemberTypes()->get('dmt1')->getId(),
            'dmt2' => $domain->getDomainMemberTypes()->get('dmt2')->getId(),
            'ct1f1' => $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId(),
            'ct1f2' => $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId(),
            'st1f1' => $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId(),
            'st1f2' => $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId(),
            'dmt1f1' => $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId(),
            'dmt1f2' => $domain->getDomainMemberTypes()->get('dmt2')->getFields()->get('f2')->getId(),
        ];

        $domain->setFromEntity($updateDomain);
        $this->assertCount(0, static::$container->get('validator')->validate($domain));
        $this->em->flush($domain);
        $domain = $this->em->find('UniteCMSCoreBundle:Domain', $domain->getId());

        $this->assertCount(2, $domain->getContentTypes());
        $this->assertCount(2, $domain->getSettingTypes());
        $this->assertCount(2, $domain->getDomainMemberTypes());
        $this->assertCount(1, $domain->getContentTypes()->get('ct1')->getFields());
        $this->assertCount(1, $domain->getContentTypes()->get('ct2')->getFields());
        $this->assertCount(1, $domain->getSettingTypes()->get('st1')->getFields());
        $this->assertCount(1, $domain->getSettingTypes()->get('st2')->getFields());
        $this->assertCount(1, $domain->getDomainMemberTypes()->get('dmt1')->getFields());
        $this->assertCount(1, $domain->getDomainMemberTypes()->get('dmt2')->getFields());
        $this->assertEquals($domainIds->ct1, $domain->getContentTypes()->get('ct1')->getId());
        $this->assertEquals($domainIds->ct2, $domain->getContentTypes()->get('ct2')->getId());
        $this->assertEquals($domainIds->st1, $domain->getSettingTypes()->get('st1')->getId());
        $this->assertEquals($domainIds->st2, $domain->getSettingTypes()->get('st2')->getId());
        $this->assertEquals($domainIds->dmt1, $domain->getDomainMemberTypes()->get('dmt1')->getId());
        $this->assertEquals($domainIds->dmt2, $domain->getDomainMemberTypes()->get('dmt2')->getId());
        $this->assertEquals($domainIds->ct1f1, $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->st1f1, $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId());
        $this->assertEquals($domainIds->dmt1f1, $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId());
    }

    // Case 5: Domain have the less ContentTypes and SettingTypes
    public function testSetIdsFromOriginWithLessContentTypes()
    {
        $domain = $this->setUpOriginDomain();
        $updateDomain = new Domain();
        $updateDomain->setOrganization($domain->getOrganization());
        $updateDomain->setDomainMemberTypes([]);
        $updateDomain->setIdentifier('domain')->setTitle('New Title');
        for ($i = 1; $i <= 1; $i++) {
            $ct = new ContentType();
            $ct->setIdentifier('ct'.$i)->setTitle('Ct'.$i);
            $ct->setDomain($updateDomain);
            $st = new SettingType();
            $st->setIdentifier('st'.$i)->setTitle('St'.$i);
            $st->setDomain($updateDomain);
            $dmt = new DomainMemberType();
            $dmt->setIdentifier('dmt'.$i)->setTitle('Dmt'.$i);
            $dmt->setDomain($updateDomain);
        }

        $domainIds = (object)[
            'ct1' => $domain->getContentTypes()->get('ct1')->getId(),
            'ct2' => $domain->getContentTypes()->get('ct2')->getId(),
            'st1' => $domain->getSettingTypes()->get('st1')->getId(),
            'st2' => $domain->getSettingTypes()->get('st2')->getId(),
            'dmt1' => $domain->getDomainMemberTypes()->get('dmt1')->getId(),
            'dmt2' => $domain->getDomainMemberTypes()->get('dmt2')->getId(),
            'ct1f1' => $domain->getContentTypes()->get('ct1')->getFields()->get('f1')->getId(),
            'ct1f2' => $domain->getContentTypes()->get('ct2')->getFields()->get('f2')->getId(),
            'st1f1' => $domain->getSettingTypes()->get('st1')->getFields()->get('f1')->getId(),
            'st1f2' => $domain->getSettingTypes()->get('st2')->getFields()->get('f2')->getId(),
            'dmt1f1' => $domain->getDomainMemberTypes()->get('dmt1')->getFields()->get('f1')->getId(),
            'dmt1f2' => $domain->getDomainMemberTypes()->get('dmt2')->getFields()->get('f2')->getId(),
        ];

        $domain->setFromEntity($updateDomain);
        $this->assertCount(0, static::$container->get('validator')->validate($domain));
        $this->em->flush($domain);
        $domain = $this->em->find('UniteCMSCoreBundle:Domain', $domain->getId());

        $this->assertCount(1, $updateDomain->getContentTypes());
        $this->assertCount(1, $updateDomain->getSettingTypes());
        $this->assertCount(1, $updateDomain->getDomainMemberTypes());
        $this->assertEquals($domainIds->ct1, $domain->getContentTypes()->get('ct1')->getId());
        $this->assertEquals($domainIds->st1, $domain->getSettingTypes()->get('st1')->getId());
        $this->assertEquals($domainIds->dmt1, $domain->getDomainMemberTypes()->get('dmt1')->getId());
    }

    public function testValidateDomainInvite()
    {

        $domain = $this->setUpOriginDomain();

        $org2 = new Organization();
        $org2->setTitle('org2')->setIdentifier('Org2');
        $domain2 = new Domain();
        $domain2->setOrganization($org2)->setTitle('Domain2')->setIdentifier('domain2');

        //Validate empty invite.
        $invite1 = new DomainInvitation();
        $errors = static::$container->get('validator')->validate($invite1);
        $this->assertCount(4, $errors);
        $this->assertStringStartsWith('domainMemberType', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());
        $this->assertStringStartsWith('email', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());
        $this->assertStringStartsWith('token', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());
        $this->assertStringStartsWith('requestedAt', $errors->get(3)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(3)->getMessageTemplate());


        //Validate invalid email
        $invite1->setDomainMemberType($domain->getDomainMemberTypes()->first());
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

        $this->em->persist($domain);
        $this->em->persist($invite1);
        $this->em->flush($invite1);

        // Validate invite uniqueness
        $invite2 = new DomainInvitation();

        $invite2->setDomainMemberType($domain->getDomainMemberTypes()->first());
        $invite2->setEmail('user1@example.com');
        $invite2->setToken('XXX')->setRequestedAt(new \DateTime());
        $errors = static::$container->get('validator')->validate($invite2);
        $this->assertCount(2, $errors);
        $this->assertStringStartsWith('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('email_already_invited', $errors->get(0)->getMessageTemplate());
        $this->assertStringStartsWith('token', $errors->get(1)->getPropertyPath());
        $this->assertEquals('token_already_present', $errors->get(1)->getMessageTemplate());

        $domainMemberType2 = new DomainMemberType();
        $domainMemberType2->setIdentifier('dmt3')->setTitle('DMT3');
        $domain->addDomainMemberType($domainMemberType2);

        $invite2->setDomainMemberType($domainMemberType2);
        $invite2->setEmail('user1@example.com')->setToken('YYY');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        $invite2->setDomainMemberType($domain2->getDomainMemberTypes()->first());
        $invite2->setEmail('user1@example.com')->setToken('YYY');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        $invite2->setDomainMemberType($domain->getDomainMemberTypes()->first());
        $invite2->setEmail('user2@example.com');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        $invite2->setDomainMemberType($domain2->getDomainMemberTypes()->first());
        $invite2->setEmail('user2@example.com');
        $this->assertCount(0, static::$container->get('validator')->validate($invite2));

        // Validate invite email cannot be the email address of a member of this organization.
        $user1 = new User();
        $user1->setPassword('XXX')->setName('XXX')->setEmail('org1user@example.com');
        $org1Member = new OrganizationMember();
        $org1Member->setUser($user1);
        $domain->getOrganization()->addMember($org1Member);

        $invite2->setEmail('org1user@example.com');
        $invite2->setDomainMemberType($domain->getDomainMemberTypes()->first());
        $errors = static::$container->get('validator')->validate($invite2);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('email', $errors->get(0)->getPropertyPath());
        $this->assertEquals('email_already_member', $errors->get(0)->getMessageTemplate());
    }

    public function testReservedIdentifiers()
    {
        $reserved = Domain::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $org = new Organization();
        $org->setTitle('org')->setIdentifier('Org');

        $domain = new Domain();
        $domain->setTitle('title')->setOrganization($org)->setIdentifier(array_pop($reserved));
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('reserved_identifier', $errors->get(0)->getMessageTemplate());
    }
}
