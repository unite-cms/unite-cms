<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class CheckboxFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('checkbox');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('checkbox');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testSettingTypeFieldTypeWithEmptySettings()
    {

        // Setting Type Field with empty settings should be valid.
        $stField = $this->createSettingTypeField('checkbox');
        $this->assertCount(0, static::$container->get('validator')->validate($stField));
    }

    public function testSettingTypeFieldTypeWithInvalidSettings()
    {

        // Setting Type Field with invalid settings should not be valid.
        $stField = $this->createSettingTypeField('checkbox');
        $stField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = static::$container->get('validator')->validate($stField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }
}
