<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class IntegerFieldTypeTest extends FieldTypeTestCase
{
    public function testIntegerTypeFieldTypeWithEmptySettings()
    {
        // Integer Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('integer');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testIntegerTypeFieldTypeWithInvalidSettings()
    {
        // Integer Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('integer');
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

    public function testIntegerTypeFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('integer');
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'not_empty' => true,
                'description' => 'my description',
                'default' => 123
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}
