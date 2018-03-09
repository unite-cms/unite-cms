<?php

namespace UnitedCMS\CoreBundle\Tests\Field;

use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;

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
}