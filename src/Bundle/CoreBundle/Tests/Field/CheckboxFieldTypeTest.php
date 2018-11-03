<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class CheckboxFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {

        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('checkbox');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('checkbox');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'required' => 123,
                'initial_data' => true
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('noboolean_value', $errors->get(0)->getMessageTemplate());
        #$this->assertEquals('nostring_value', $errors->get(1)->getMessageTemplate());
    }

    public function testFormFormSubmit() {

        $ctField = $this->createContentTypeField('checkbox');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'initial_data' => true
            ]
        ));

        $content = new Content();

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([]);

        $this->assertTrue($form->getData()[$ctField->getIdentifier()]);
    }
}
