<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class DateFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {
        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('date');
        $this->assertCount(0, $this->container->get('validator')->validate($ctField));
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {
        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('date');
        $ctField->setSettings(new FieldableFieldSettings(['widget' => 'test']));
        $errors = $this->container->get('validator')->validate($ctField);

        $this->assertCount(1, $errors);
        $this->assertEquals('validation.wrong_widget_value', $errors->get(0)->getMessage());

        $ctField->setSettings(new FieldableFieldSettings(['required' => 'test']));
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.no_boolean_value', $errors->get(0)->getMessage());
    }

    public function testContentTypeFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('date');
        $ctField->setSettings(new FieldableFieldSettings(['widget' => 'text', 'required' => true]));
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }
}
