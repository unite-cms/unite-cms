<?php

namespace UnitedCMS\CoreBundle\Tests\Field;

use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;

class ChoiceFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('choice');
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());
    }

    public function testContentTypeFieldTypeWithInvalidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choice');
        $ctField->setSettings(new FieldableFieldSettings(['choices' => ['foo' => 'baa'], 'foo' => 'baa']));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testContentTypeFieldTypeWithValidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choice');
        $ctField->setSettings(new FieldableFieldSettings(['choices' => ['foo' => 'baa']]));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}