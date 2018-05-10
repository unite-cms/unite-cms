<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class PhoneFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {
        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('phone');
        $this->assertCount(0, $this->container->get('validator')->validate($ctField));
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {
        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('phone');
        $ctField->setSettings(new FieldableFieldSettings(['required' => 'baa']));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.no_boolean_value', $errors->get(0)->getMessage());
    }

    public function testContentTypeFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('phone');
        $ctField->setSettings(new FieldableFieldSettings(['required' => true]));
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}
