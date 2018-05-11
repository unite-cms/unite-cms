<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class NumberFieldTypeTest extends FieldTypeTestCase
{
    public function testNumberTypeFieldTypeWithEmptySettings()
    {
        // Number Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('number');
        $this->assertCount(0, $this->container->get('validator')->validate($ctField));
    }

    public function testNumberTypeFieldTypeWithInvalidSettings()
    {
        // Number Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('number');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }
}
