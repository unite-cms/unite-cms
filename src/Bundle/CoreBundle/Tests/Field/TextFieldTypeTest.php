<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class TextFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {
        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('text');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('text');
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'foo' => 'baa',
                'required' => 'foo',
                'empty_data' => true
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());
    }
}
