<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class TextAreaTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('textarea');
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testAllowedRowsSetting()
    {
        $field = $this->createContentTypeField('textarea');
        $field->setSettings(
          new FieldableFieldSettings(
            [
              'rows' => 'abc',
            ]
          )
        );
        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.rows', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.nointeger_value', $errors->get(0)->getMessage());

        $field->setSettings(
          new FieldableFieldSettings(
            [
              'rows' => 20,
            ]
          )
        );
        $this->assertCount(0, $this->container->get('validator')->validate($field));
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('textarea');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }
}
