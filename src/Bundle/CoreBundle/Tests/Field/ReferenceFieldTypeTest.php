<?php

namespace UnitedCMS\CoreBundle\Tests\Field;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\View\ViewParameterBag;

class ReferenceFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());
        $this->assertContains('settings.domain', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.required', $errors->get(1)->getMessage());
        $this->assertContains('settings.content_type', $errors->get(1)->getPropertyPath());
    }

    public function testContentTypeFieldTypeWithInvalidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'foo',
            'content_label' => 'laa',
            'foo' => 'baa'
        ]));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testContentTypeFieldTypeWithValidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'foo',
            'content_label' => 'laa',
        ]));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testContentTypeFieldContentLabelFallback() {

        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('baa');

        // Fake organization and domain
        $fieldType = $this->container->get('united.cms.field_type_manager')->getFieldType($ctField->getType());

        $o1 = new \ReflectionProperty($fieldType, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($fieldType, $authMock);

        $o2 = new \ReflectionProperty($fieldType, 'unitedCMSManager');
        $o2->setAccessible(true);
        $cmsManager = $this->createMock(UnitedCMSManager::class);
        $cmsManager->expects($this->any())->method('getOrganization')->willReturn($ctField->getContentType()->getDomain()->getOrganization());
        $cmsManager->expects($this->any())->method('getDomain')->willReturn($ctField->getContentType()->getDomain());
        $o2->setValue($fieldType, $cmsManager);

        $o3 = new \ReflectionProperty($fieldType, 'entityManager');
        $o3->setAccessible(true);
        $repositoryMock = $this->createMock(EntityRepository::class);
        $repositoryMock->expects($this->any())->method('findOneBy')->willReturn($ctField->getContentType()->getView('all'));
        $cmsManager = $this->createMock(EntityManager::class);
        $cmsManager->expects($this->any())->method('getRepository')->willReturn($repositoryMock);
        $o3->setValue($fieldType, $cmsManager);



        // No content_label fallback and no content_label set.
        $ctField->getContentType()->setContentLabel('');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'all',
        ]));

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals((string)$ctField->getContentType() . ' #{id}', $options['attr']['content-label']);

        // No content_label empty and fallback present
        $ctField->getContentType()->setContentLabel('foo');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'all',
        ]));

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals('foo', $options['attr']['content-label']);

        // content_label present and fallback present
        $ctField->getContentType()->setContentLabel('foo');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'content_label' => 'baa',
            'view' => 'all',
        ]));

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals('baa', $options['attr']['content-label']);
    }
}