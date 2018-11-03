<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ChoiceFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('choice');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choice');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo' => 'baa'],
                'foo' => 'baa',
                'required' => 123,
                'initial_data' => true
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        #$this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());

        // check wrong empty data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo1' => 'foo1', 'foo2' => 'foo2'],
                'initial_data' => 'baa'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        #$this->assertCount(1, $errors);
        #$this->assertEquals('emptydata_not_inside_values', $errors->get(0)->getMessageTemplate());

    }

    public function testContentTypeFieldTypeWithValidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choice');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo' => 'baa'],
                'initial_data' => 'baa'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}
