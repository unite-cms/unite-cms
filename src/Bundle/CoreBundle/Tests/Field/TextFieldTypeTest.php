<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
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
                'initial_data' => true
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        #$this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());
    }

    public function testFormFormSubmit() {

        $ctField = $this->createContentTypeField('text');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'initial_data' => 'test'
            ]
        ));

        $content = new Content();
        $content->setContentType($ctField->getContentType());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        $this->assertEquals('test', $form->get($ctField->getIdentifier())->getData());

    }
}
