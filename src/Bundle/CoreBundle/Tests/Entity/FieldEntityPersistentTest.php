<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class FieldEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateField()
    {

        // Try to validate empty Field.
        $field = new ContentTypeField();
        $field->setIdentifier('')->setTitle('')->setType('');
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(5, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('type', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        $this->assertEquals('type', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_field_type', $errors->get(3)->getMessage());

        $this->assertEquals('contentType', $errors->get(4)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(4)->getMessage());

        // Try to validate too long title, identifier, type
        $field
            ->setTitle($this->generateRandomUTF8String(256))
            ->setIdentifier($this->generateRandomMachineName(256))
            ->setType($this->generateRandomMachineName(256))
            ->setEntity(new ContentType())
            ->getEntity()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(4, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        $this->assertEquals('type', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(2)->getMessage());

        $this->assertEquals('type', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_field_type', $errors->get(3)->getMessage());

        // Try to validate invalid type
        $field
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier('identifier')
            ->setType('invalid');
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('type', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_field_type', $errors->get(0)->getMessage());

        // Try to validate invalid identifier
        $field
            ->setIdentifier('#')
            ->setType('text');

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Test UniqueEntity Validation.
        $field->setIdentifier('identifier');
        $this->em->persist($field->getEntity()->getDomain()->getOrganization());
        $this->em->persist($field->getEntity()->getDomain());
        $this->em->persist($field);
        $this->em->flush($field);
        $this->em->refresh($field);

        $field2 = new ContentTypeField();
        $field2
            ->setTitle($field->getTitle())
            ->setIdentifier($field->getIdentifier())
            ->setEntity($field->getEntity())
            ->setType($field->getType());

        $errors = static::$container->get('validator')->validate($field2);
        $this->assertCount(1, $errors);
        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.identifier_already_taken', $errors->get(0)->getMessage());
    }

    public function testValidateFieldSettingsValidation()
    {

        // 1. Create Content Type with 1 mocked FieldType
        $mockedFieldType = new Class extends FieldType
        {
            const TYPE = "field_entity_test_mocked_field";

            function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
            {
                if (isset($settings->invalid)) {
                    return [
                        new ConstraintViolation(
                            'mocked_message',
                            'mocked_message',
                            [],
                            $settings,
                            '',
                            $settings
                        ),
                    ];
                }

                return [];
            }
        };

        // Inject the field type
        static::$container->get('unite.cms.field_type_manager')->registerFieldType($mockedFieldType);

        $field = new ContentTypeField();
        $field
            ->setType('field_entity_test_mocked_field')
            ->setIdentifier('invalid')
            ->setTitle('Title')
            ->setEntity(new ContentType())
            ->getEntity()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        // 2. Set invalid field settings.
        $field->setSettings(new FieldableFieldSettings(['invalid' => true]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings', $errors->get(0)->getPropertyPath());
        $this->assertEquals('mocked_message', $errors->get(0)->getMessage());

        // 3. Set valid field settings.
        $field->setSettings(new FieldableFieldSettings(['other' => true]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));
    }

    public function testContentFieldWeight()
    {
        $contentType = new ContentType();
        $contentType->setIdentifier('ct')->setTitle('CT');
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');
        $contentType->setDomain($domain);

        $field1 = new ContentTypeField();
        $field1->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($contentType);
        $field2 = new ContentTypeField();
        $field2->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($contentType);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($field1);
        $this->em->refresh($field2);
        $this->assertEquals(0, $field1->getWeight());
        $this->assertEquals(1, $field2->getWeight());

        // Reorder
        $this->em->flush($domain);
        $this->em->refresh($domain);

        $reorderedContentType = new ContentType();
        $reorderedContentType->setDomain($contentType->getDomain())->setTitle($contentType->getTitle())->setIdentifier(
            $contentType->getIdentifier()
        );

        $field1_clone = clone $field1;
        $field1_clone->setWeight(null);
        $field2_clone = clone $field2;
        $field2_clone->setWeight(null);
        $reorderedContentType->addField($field2_clone)->addField($field1_clone);
        $contentType->setFromEntity($reorderedContentType);

        $this->em->flush();
        $this->em->refresh($contentType);
        $this->assertEquals(1, $field1->getWeight());
        $this->assertEquals(0, $field2->getWeight());
    }

    public function testSettingFieldWeight()
    {
        $settingType = new SettingType();
        $settingType->setIdentifier('st')->setTitle('ST');
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');
        $settingType->setDomain($domain);

        $field1 = new SettingTypeField();
        $field1->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($settingType);
        $field2 = new SettingTypeField();
        $field2->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($settingType);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($field1);
        $this->em->refresh($field2);
        $this->assertEquals(0, $field1->getWeight());
        $this->assertEquals(1, $field2->getWeight());

        // Reorder
        $settingType->getFields()->remove('f1');
        $settingType->getFields()->get('f2')->setWeight(0);
        $field1->setWeight(null);

        $settingType->addField($field1);

        $this->em->flush();
        $this->em->refresh($field1);
        $this->em->refresh($field2);
        $this->assertEquals(1, $field1->getWeight());
        $this->assertEquals(0, $field2->getWeight());
    }

    public function testDomainMemberFieldWeight()
    {
        $domainMemberType = new DomainMemberType();
        $domainMemberType->setIdentifier('st')->setTitle('ST');
        $org = new Organization();
        $org->setTitle('Org')->setIdentifier('org');
        $domain = new Domain();
        $domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');
        $domainMemberType->setDomain($domain);

        $field1 = new DomainMemberTypeField();
        $field1->setTitle('F1')->setIdentifier('f1')->setType('text')->setEntity($domainMemberType);
        $field2 = new DomainMemberTypeField();
        $field2->setTitle('F2')->setIdentifier('f2')->setType('text')->setEntity($domainMemberType);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->flush();
        $this->em->refresh($field1);
        $this->em->refresh($field2);
        $this->assertEquals(0, $field1->getWeight());
        $this->assertEquals(1, $field2->getWeight());

        // Reorder
        $domainMemberType->getFields()->remove('f1');
        $domainMemberType->getFields()->get('f2')->setWeight(0);
        $field1->setWeight(null);

        $domainMemberType->addField($field1);

        $this->em->flush();
        $this->em->refresh($field1);
        $this->em->refresh($field2);
        $this->assertEquals(1, $field1->getWeight());
        $this->assertEquals(0, $field2->getWeight());
    }

    public function testReservedIdentifiers()
    {
        $reserved = ContentTypeField::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $ctf = new ContentTypeField();
        $ctf->setTitle('title')->setIdentifier(array_pop($reserved))
            ->setType('text')
            ->setEntity(new ContentType())
            ->getEntity()->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');
        $errors = static::$container->get('validator')->validate($ctf);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());

        $reserved = SettingTypeField::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $stf = new SettingTypeField();
        $stf->setTitle('title')->setIdentifier(array_pop($reserved))
            ->setType('text')
            ->setEntity(new SettingType())
            ->getEntity()->setIdentifier('st')->setTitle('st')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');;
        $errors = static::$container->get('validator')->validate($stf);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());

        $reserved = DomainMemberTypeField::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $dmtf = new DomainMemberTypeField();
        $dmtf->setTitle('title')->setIdentifier(array_pop($reserved))
            ->setType('text')
            ->setEntity(new DomainMemberType())
            ->getEntity()->setIdentifier('dmt')->setTitle('dmt')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');;
        $errors = static::$container->get('validator')->validate($dmtf);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());
    }
}
