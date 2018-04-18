<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class SettingTypeEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateContentType()
    {

        // Try to validate empty SettingType.
        $settingType = new SettingType();
        $settingType->setIdentifier('')->setTitle('')->setDescription('')->setIcon('');
        $errors = $this->container->get('validator')->validate($settingType);
        $this->assertCount(4, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('domain', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        $this->assertEquals('permissions', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_selection', $errors->get(3)->getMessage());

        // Try to save a too long icon name or an icon name with special chars.
        $settingType->setTitle('st1')->setIdentifier('st1')->setDomain(new Domain());
        $settingType->setIcon($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $settingType->setIcon('# ');
        $errors = $this->container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Try to save invalid title.
        $settingType->setIcon(null)->setTitle($this->generateRandomUTF8String(256));
        $errors = $this->container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        // Try to save invalid identifier.
        $settingType->setTitle($this->generateRandomUTF8String(255))->setIdentifier('X ');
        $errors = $this->container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        $settingType->setIdentifier($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        // There can only be one identifier per domain with the same identifier.
        $org = new Organization();
        $org->setIdentifier('org1')->setTitle('Org 1');
        $domain1 = new Domain();
        $domain1->setIdentifier('domain1')->setTitle('Domain1');
        $domain2 = new Domain();
        $domain2->setIdentifier('domain2')->setTitle('Domain12');
        $org->addDomain($domain1);
        $org->addDomain($domain2);
        $settingType = new SettingType();
        $settingType->setIdentifier('domain1_st1')->setTitle('domain1_ct1')->setDomain($domain1);
        $this->em->persist($org);
        $this->em->persist($domain1);
        $this->em->persist($domain2);
        $this->em->persist($settingType);
        $this->em->flush($settingType);
        $this->assertCount(0, $this->container->get('validator')->validate($settingType));

        // CT2 one the same domain with the same identifier should not be valid.
        $settingType2 = new SettingType();
        $settingType2->setIdentifier('domain1_st1')->setTitle('domain1_st1')->setDomain($domain1);
        $this->assertCount(1, $this->container->get('validator')->validate($settingType2));
        $errors = $this->container->get('validator')->validate($settingType2);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.identifier_already_taken', $errors->get(0)->getMessage());

        $settingType2->setIdentifier('domain1_st2');
        $this->assertCount(0, $this->container->get('validator')->validate($settingType2));
    }

    public function testDeleteDomainWithSettingType()
    {

        $org = new Organization();
        $org->setIdentifier('org1')->setTitle('Org 1');
        $domain1 = new Domain();
        $domain1->setIdentifier('domain1')->setTitle('Domain1');
        $org->addDomain($domain1);
        $settingType = new SettingType();
        $settingType->setIdentifier('domain1_st1')->setTitle('domain1_ct1')->setDomain($domain1);
        $this->em->persist($org);
        $this->em->persist($domain1);
        $this->em->persist($settingType);
        $this->em->flush($settingType);

        $setting = new Setting();
        $setting->setSettingType($settingType);
        $this->em->persist($setting);
        $this->em->flush($setting);

        $this->assertCount(0, $this->container->get('validator')->validate($settingType, null, ['DELETE']));
        $this->assertCount(1, $this->em->getRepository('UniteCMSCoreBundle:Setting')->findAll());
        $this->em->remove($settingType);
        $this->em->flush();
        $this->assertCount(0, $this->em->getRepository('UniteCMSCoreBundle:Setting')->findAll());

    }

    public function testSettingTypeWeight()
    {
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');

        $st1 = new SettingType();
        $st1->setIdentifier('st1')->setTitle('ST1');
        $domain->addSettingType($st1);

        $st2 = new SettingType();
        $st2->setIdentifier('st2')->setTitle('ST2');
        $domain->addSettingType($st2);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($st1);
        $this->em->refresh($st2);
        $this->assertEquals(0, $st1->getWeight());
        $this->assertEquals(1, $st2->getWeight());

        // Reorder
        $reorderedDomain = new Domain();
        $reorderedDomain->setOrganization($org)->setTitle($domain->getTitle())->setIdentifier($domain->getIdentifier());
        $reorderedDomain->addSettingType(clone $st2)->addSettingType(clone $st1);
        $domain->setFromEntity($reorderedDomain);

        $this->em->flush($domain);
        $this->em->refresh($domain);
        $this->assertEquals(1, $st1->getWeight());
        $this->assertEquals(0, $st2->getWeight());
    }

    public function testReservedIdentifiers()
    {
        $reserved = SettingType::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $st = new SettingType();
        $st->setTitle('title')->setIdentifier(array_pop($reserved))->setDomain(new Domain());
        $errors = $this->container->get('validator')->validate($st);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());
    }
}
