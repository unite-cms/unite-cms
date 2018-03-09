<?php

namespace UnitedCMS\CoreBundle\Tests\Field;

use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;

class RangeFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('range');
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testContentTypeFieldTypeWithInvalidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('range');
        $ctField->setSettings(new FieldableFieldSettings(['min' => 0, 'max' => 100, 'step' => 1, 'foo' => 'baa']));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testContentTypeFieldTypeWithValidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('range');
        $ctField->setSettings(new FieldableFieldSettings(['min' => 0, 'max' => 100, 'step' => 1]));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}