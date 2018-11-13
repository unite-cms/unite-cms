<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Service\UniteCMSManager;

class ReferenceOfFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('reference_of');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
        $this->assertContains('settings.domain', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(1)->getMessageTemplate());
        $this->assertContains('settings.content_type', $errors->get(1)->getPropertyPath());
        $this->assertEquals('required', $errors->get(2)->getMessageTemplate());
        $this->assertContains('settings.reference_field', $errors->get(2)->getPropertyPath());
    }

    public function testContentTypeFieldTypeWithInvalidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference_of');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'reference_field' => 'foo',
            'foo' => 'baa'
        ]));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
    }

    public function testContentTypeFieldTypeWithInvalidDomainAndContentTypeSetting() {

        $ctField = $this->createContentTypeField('reference_of');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('foo')->setTitle('Foo');
        $ctField->getContentType()->setIdentifier('baa');

        $otherDomain = new Domain();
        $otherDomain->setTitle('Other')->setIdentifier('other')->setOrganization($ctField->getContentType()->getDomain()->getOrganization());
        $otherContentType = new ContentType();
        $otherContentType->setIdentifier('other')->setTitle('Other');
        $otherDomain->addContentType($otherContentType);
        $ctField->getContentType()->getDomain()->getOrganization()->addDomain($otherDomain);

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($otherDomain);
        $this->em->persist($otherContentType);
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->flush();
        $this->em->refresh($ctField->getContentType()->getDomain());

        $domain = $ctField->getContentType()->getDomain();
        $this->assertNotNull($ctField->getContentType()->getDomain()->getId());

        // Fake organization
        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o01 = new \ReflectionProperty($fieldType, 'referenceResolver');
        $o01->setAccessible(true);
        $referenceResolver = $o01->getValue($fieldType);

        $o1 = new \ReflectionProperty($o01->getValue($fieldType), 'authorizationChecker');
        $o1->setAccessible(true);
        $o1->setValue($referenceResolver, new class implements AuthorizationCheckerInterface {
            public function isGranted($attributes, $subject = null) { return true; }
        });

        $refFieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType('reference');

        $o02 = new \ReflectionProperty($refFieldType, 'referenceResolver');
        $o02->setAccessible(true);
        $refReferenceResolver = $o02->getValue($refFieldType);

        $o2 = new \ReflectionProperty($o02->getValue($refFieldType), 'authorizationChecker');
        $o2->setAccessible(true);
        $o2->setValue($refReferenceResolver, new class implements AuthorizationCheckerInterface {
            public function isGranted($attributes, $subject = null) { return true; }
        });

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'organization');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain()->getOrganization());

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'domain');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain());



        // Content Type Field with invalid settings should not be valid.
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo234',
            'content_type' => 'baa2323',
            'reference_field' => 'ref',
        ]));

        // ContentType and Domain does not exist.
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // Domain exist, content type does not exist.
        $ctField->getSettings()->domain = 'foo';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_content_type', $errors->get(0)->getMessageTemplate());

        // Content type exist, domain does not exist.
        $ctField->getSettings()->domain = 'wrong';
        $ctField->getSettings()->content_type = 'baa';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // No view access on domain.
        $ctField->getSettings()->domain = 'foo';
        $ctField->getSettings()->content_type = 'baa';

        $o1->setValue($referenceResolver, new class implements AuthorizationCheckerInterface {
            public function isGranted($attributes, $subject = null) { return false; }
        });

        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // View access but no reference field exists.
        $o1->setValue($referenceResolver, new class implements AuthorizationCheckerInterface {
            public function isGranted($attributes, $subject = null) { return true; }
        });

        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_field', $errors->get(0)->getMessageTemplate());

        // Test if this domain is currently updated for the new content_type but not saved.
        $contentType = new ContentType();
        $contentType->setIdentifier('new_ct')->setTitle('new_ct');
        $domain->addContentType($contentType);

        $ctField->getSettings()->domain = 'foo';
        $ctField->getSettings()->content_type = 'wrong_new_ct';
        $errors = static::$container->get('validator')->validate($domain);

        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_content_type', $errors->get(0)->getMessageTemplate());

        $ctField->getSettings()->domain = 'foo';
        $ctField->getSettings()->content_type = 'new_ct';

        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_field', $errors->get(0)->getMessageTemplate());

        // Test for freshly created domains.
        $ctField = $this->createContentTypeField('reference_of');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('new')->setTitle('Foo');
        $ctField->getContentType()->setIdentifier('baa');
        $domain = $ctField->getContentType()->getDomain();

        // Create 2nd content type
        $ct2 = new ContentType();
        $ct2->setTitle('CT2')->setIdentifier('ct2');
        $domain->addContentType($ct2);

        // wrong domain name and wrong content_type
        $ctField->getSettings()->domain = 'wrong';
        $ctField->getSettings()->content_type = 'wrong';
        $ctField->getSettings()->reference_field = 'wrong';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // wrong content_type
        $ctField->getSettings()->domain = 'new';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_content_type', $errors->get(0)->getMessageTemplate());

        // wrong domain
        $ctField->getSettings()->domain = 'wrong';
        $ctField->getSettings()->content_type = 'ct2';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // Correct content_type and domain but no reference field
        $ctField->getSettings()->domain = 'new';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_field', $errors->get(0)->getMessageTemplate());

        // Reference field exists but is of wrong type
        $ref_field = new ContentTypeField();
        $ref_field->setType('text')->setIdentifier('ref')->setTitle('Ref');
        $ct2->addField($ref_field);
        $ctField->getSettings()->reference_field = 'ref';

        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_field', $errors->get(0)->getMessageTemplate());

        // Reference field exists and is of correct type but not for this content type and domain.
        $ref_field->setType('reference');
        $ref_field->getSettings()->domain = 'new';
        $ref_field->getSettings()->content_type = 'ct2';

        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_field_reference', $errors->get(0)->getMessageTemplate());

        // Reference field exists and is of correct type and for this domain and ct
        $ref_field->getSettings()->domain = 'new';
        $ref_field->getSettings()->content_type = 'baa';
        $this->assertCount(0, static::$container->get('validator')->validate($domain));
    }

    public function testFormOptionGeneration() {

        $ctField = $this->createContentTypeField('reference_of');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('org');
        $ctField->getContentType()->getDomain()->setIdentifier('domain');
        $ctField->getContentType()->setIdentifier('ct');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'domain',
            'content_type' => 'ct2',
            'reference_field' => 'reference',
        ]));

        // Create other reference field
        $ctRefField = $this->createContentTypeField('reference');
        $ctRefField->getContentType()->setDomain($ctField->getContentType()->getDomain());
        $ctRefField->getContentType()->setIdentifier('ct2');
        $ctRefField->setIdentifier('reference');
        $ctRefField->setSettings(new FieldableFieldSettings([
            'domain' => 'domain',
            'content_type' => 'ct',
        ]));

        // Fake organization and domain
        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o01 = new \ReflectionProperty($fieldType, 'referenceResolver');
        $o01->setAccessible(true);
        $referenceResolver = $o01->getValue($fieldType);

        $o1 = new \ReflectionProperty($referenceResolver, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($referenceResolver, $authMock);

        $o2 = new \ReflectionProperty($referenceResolver, 'uniteCMSManager');
        $o2->setAccessible(true);
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $cmsManager->expects($this->any())->method('getOrganization')->willReturn($ctField->getContentType()->getDomain()->getOrganization());
        $cmsManager->expects($this->any())->method('getDomain')->willReturn($ctField->getContentType()->getDomain());
        $o2->setValue($referenceResolver, $cmsManager);

        $o31 = new \ReflectionProperty($referenceResolver, 'entityManager');
        $o31->setAccessible(true);

        $viewRepositoryMock = $this->createMock(EntityRepository::class);
        $viewRepositoryMock->expects($this->any())->method('findOneBy')->willReturn($ctField->getContentType()->getView('all'));
        $domainRepositoryMock = $this->createMock(EntityRepository::class);
        $domainRepositoryMock->expects($this->any())->method('findOneBy')->willReturn($ctField->getContentType()->getDomain());

        $cmsManager = $this->createMock(EntityManager::class);
        $cmsManager->expects($this->any())->method('getRepository')->will($this->returnValueMap([
            ['UniteCMSCoreBundle:View', $viewRepositoryMock],
            ['UniteCMSCoreBundle:Domain', $domainRepositoryMock],
        ]));
        $o31->setValue($referenceResolver, $cmsManager);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals($options['label'], $ctField->getTitle());
        $this->assertEquals($options['view'], $ctRefField->getContentType()->getView('all'));
        $this->assertEquals($options['reference_field'], $ctRefField);
    }
}
