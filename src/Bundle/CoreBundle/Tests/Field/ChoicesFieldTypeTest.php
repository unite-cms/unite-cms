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
                'not_empty' => 123,
                'default' => true,
                'description' => 123
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(4, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('invalid_initial_data', $errors->get(3)->getMessageTemplate());

        // check wrong empty data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo1' => 'foo1', 'foo2' => 'foo2', 'foo3' => 'foo3'],
                'default' => ['baaa']
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('The value you selected is not a valid choice.', $errors->get(0)->getMessageTemplate());

    }

    public function testContentTypeFieldTypeWithValidSettings()
    {
        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('choices');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'choices' => ['foo1' => 'foo1', 'foo2' => 'foo2', 'foo3' => 'foo3'],
                'default' => ['foo1', 'foo2']
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

}