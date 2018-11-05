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
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('noboolean_value', $errors->get(0)->getMessageTemplate());
    }

    public function testFormSubmit() {

        $ctField = $this->createContentTypeField('checkbox');

        // check required
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'required' => true
            ]
        ));

        $content = new Content();

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([]);
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSubmitted());
        $error_check = [];
        foreach ($form->getErrors(true, true) as $error) {
            $error_check[] = $error->getMessageTemplate();
        }
        $this->assertCount(1, $error_check);
        $this->assertEquals('not_blank', $error_check[0]);
    }

    public function testContentFormBuild() {

        $ctField = $this->createContentTypeField('checkbox');

        $ctField->setIdentifier('f1');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('ct1');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'initial_data' => true,
                'description' => 'blabla'
            ]
        ));

        $content = new Content();
        $content->setContentType($ctField->getContentType());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );

        $formView = $form->createView();
        $root = $formView->getIterator()->current();

        $this->assertEquals(1, $root->vars['value']);
        $this->assertEquals('blabla', $root->vars['description']);

    }
}
