<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class NumberFieldTypeTest extends FieldTypeTestCase
{
    public function testNumberTypeFieldTypeWithEmptySettings()
    {
        // Number Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('number');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testNumberTypeFieldTypeWithInvalidSettings()
    {
        // Number Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('number');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // test wrong initial data
        $ctField->setSettings(new FieldableFieldSettings(['default' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_initial_data', $errors->get(0)->getMessageTemplate());
    }
}
