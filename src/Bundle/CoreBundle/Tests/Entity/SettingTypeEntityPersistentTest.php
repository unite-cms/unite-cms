<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldablePreview;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\Webhook;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class SettingTypeEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateSettingType()
    {

        // Try to validate empty SettingType.
        $settingType = new SettingType();
        $settingType->setIdentifier('')->setTitle('')->setDescription('')->setIcon('');
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(3, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());

        $this->assertEquals('domain', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());

        // Try to save a too long icon name or an icon name with special chars.
        $settingType->setTitle('st1')->setIdentifier('st1')->setDomain(new Domain());
        $settingType->setIcon($this->generateRandomMachineName(256));
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        $settingType->setIcon('# ');
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        // Try to save invalid title.
        $settingType->setIcon(null)->setTitle($this->generateRandomUTF8String(256));
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());

        // Try to save invalid identifier.
        $settingType->setTitle($this->generateRandomUTF8String(255))->setIdentifier('X ');
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_characters', $errors->get(0)->getMessageTemplate());

        $settingType->setIdentifier($this->generateRandomMachineName(256));
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('too_long', $errors->get(0)->getMessageTemplate());


        $settingType->setIdentifier('valid')->setValidations([new FieldableValidation('invalid')]);
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(1, $errors);
        $this->assertEquals('validations[0][expression]', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_validations', $errors->get(0)->getMessageTemplate());

        $settingType->setIdentifier('valid')->setValidations([new FieldableValidation('1 == 1')]);
        $this->assertCount(0, static::$container->get('validator')->validate($settingType));

        // Check that preview validation is working. Full validation is tested in ContentTypeEntityPersistentTest.
        $settingType->setPreview(null);
        $this->assertCount(0, static::$container->get('validator')->validate($settingType));

        $settingType->setPreview(new FieldablePreview('', ''));
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(2, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('preview.query', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());

        $settingType->setPreview(new FieldablePreview('XXX', 'foo'));
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(2, $errors);
        $this->assertEquals('preview.url', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_url', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('preview.query', $errors->get(1)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(1)->getMessageTemplate());

        // validate Webhooks 
        $settingType->setWebhooks([]);
        $this->assertCount(0, static::$container->get('validator')->validate($settingType));

        $settingType->setWebhooks([
            new Webhook('', '', ''),
            new Webhook('XXX', 'XXX', 'csd <= ', 'csd <= ', -1),
        ]);
        $errors = static::$container->get('validator')->validate($settingType);
        $this->assertCount(8, $errors);
        $this->assertEquals('webhooks[0].query', $errors->get(0)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('webhooks[0].url', $errors->get(1)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('webhooks[0].action', $errors->get(2)->getPropertyPath());
        $this->assertEquals('not_blank', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('webhooks[1].query', $errors->get(3)->getPropertyPath());
        $this->assertEquals('invalid_query', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('webhooks[1].url', $errors->get(4)->getPropertyPath());
        $this->assertEquals('invalid_url', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('webhooks[1].secret_key', $errors->get(5)->getPropertyPath());
        $this->assertEquals('too_short', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('webhooks[1].action', $errors->get(6)->getPropertyPath());
        $this->assertEquals('invalid_expression', $errors->get(6)->getMessageTemplate());

        $settingType->setWebhooks([
            new Webhook('{ type }', 'http://www.orf.at', 'event == "update"', false, 'abc12234234basdf'),
            new Webhook('query { foo { baa } }', 'https://www.orf.at', 'event == "delete"', true, ''),
        ]);
        $this->assertCount(0, static::$container->get('validator')->validate($settingType));

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
        $this->assertCount(0, static::$container->get('validator')->validate($settingType));

        // CT2 one the same domain with the same identifier should not be valid.
        $settingType2 = new SettingType();
        $settingType2->setIdentifier('domain1_st1')->setTitle('domain1_st1')->setDomain($domain1);
        $this->assertCount(1, static::$container->get('validator')->validate($settingType2));
        $errors = static::$container->get('validator')->validate($settingType2);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('identifier_already_taken', $errors->get(0)->getMessageTemplate());

        $settingType2->setIdentifier('domain1_st2');
        $this->assertCount(0, static::$container->get('validator')->validate($settingType2));
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

        $this->assertCount(0, static::$container->get('validator')->validate($settingType, null, ['DELETE']));
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

        $st1_clone = clone $st1;
        $st1_clone->setWeight(null);

        $st2_clone = clone $st2;
        $st2_clone->setWeight(null);

        $reorderedDomain->addSettingType($st2_clone)->addSettingType($st1_clone);
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
        $errors = static::$container->get('validator')->validate($st);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('reserved_identifier', $errors->get(0)->getMessageTemplate());
    }

    public function testValidationSerialization() {

        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $this->em->persist($org);

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier('domain')->setOrganization($org);
        $this->em->persist($domain);

        $st = new SettingType();
        $st->setIdentifier('st1')->setTitle('St1')->setDomain($domain);
        $st->setValidations([
            new FieldableValidation('true', 'first validation', 'field1'),
            new FieldableValidation('true', '2nd validation', 'field2', ['DELETE'])
        ]);
        $st->addValidation(new FieldableValidation('false', '3rd validation'));

        $this->em->persist($st);
        $this->em->flush($st);
        $st->setValidations([]);
        $this->em->clear();

        $st_reloaded = $this->em->getRepository('UniteCMSCoreBundle:SettingType')->findOneBy(['title' => 'St1']);

        $this->assertEquals($st_reloaded->getValidations(), [
            new FieldableValidation('true', 'first validation', 'field1'),
            new FieldableValidation('true', '2nd validation', 'field2', ['DELETE']),
            new FieldableValidation('false', '3rd validation')
        ]);
    }
}
