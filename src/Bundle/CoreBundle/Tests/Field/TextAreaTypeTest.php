<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class TextAreaTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('textarea');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testAllowedRowsSetting()
    {
        // check invalid row setting
        $field = $this->createContentTypeField('textarea');

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($field->getType());

        $field->setSettings(
          new FieldableFieldSettings(
            [
              'rows' => 'abc',
            ]
          )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.rows', $errors->get(0)->getPropertyPath());
        $this->assertEquals('nointeger_value', $errors->get(0)->getMessageTemplate());

        // check valid rows settings
        $field->setSettings(
          new FieldableFieldSettings(
            [
              'rows' => 20,
            ]
          )
        );
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        // Check if setting is set correctly
        $options = $fieldType->getFormOptions($field);
        $this->assertEquals(20, $options['attr']['rows']);
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('textarea');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        $ctField->setSettings(new FieldableFieldSettings(
          [
              'foo' => 'baa',
              'required' => 'foo',
              'description' => true
          ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());

        // test wrong initial data
        $ctField->setSettings(new FieldableFieldSettings(
          [
              'default' => true,
          ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_initial_data', $errors->get(0)->getMessageTemplate());
    }
}
