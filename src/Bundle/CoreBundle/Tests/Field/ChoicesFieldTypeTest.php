<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 05.11.18
 * Time: 18:23
 */

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ChoicesFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        $ctField = $this->createContentTypeField('choices');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo' => 'baa'],
                'foo' => 'baa',
                'required' => 123,
                'initial_data' => true,
                'description' => 123
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());

        // check wrong empty data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo1' => 'foo1', 'foo2' => 'foo2', 'foo3' => 'foo3'],
                'initial_data' => ['baaa']
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('initial_data_not_inside_values', $errors->get(0)->getMessageTemplate());

    }

    public function testContentTypeFieldTypeWithValidSettings()
    {
        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choices');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo1' => 'foo1', 'foo2' => 'foo2', 'foo3' => 'foo3'],
                'initial_data' => ['foo1', 'foo2']
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

}