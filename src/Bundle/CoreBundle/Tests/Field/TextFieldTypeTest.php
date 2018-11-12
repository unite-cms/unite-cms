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
                'not_empty' => 'foo',
                'description' => $this->generateRandomMachineName(500)
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('too_long', $errors->get(2)->getMessageTemplate());

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

    public function testContentFormBuild() {

        $ctField = $this->createContentTypeField('text');

        $ctField->setIdentifier('f1');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('ct1');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => 'test'
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

        $this->assertEquals('test', $root->vars['value']);

    }

    public function testStateFieldTypeTestFormSubmit()
    {
        $ctField = $this->createContentTypeField('text');
        $ctField->setSettings(new FieldableFieldSettings(['not_empty' => true]));
        $content = new Content();
        $content->setContentType($ctField->getContentType());

        // test a transition choice
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content,
            [
                'csrf_protection' => false,
            ]
        );

        $form->submit([]);
        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertEquals('This value should not be blank.', $form->getErrors(true, true)->offsetGet(0)->getMessage());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content,
            [
                'csrf_protection' => false,
            ]
        );
        $form->submit(
            [
                $ctField->getIdentifier() => '',
            ]
        );
        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertEquals('This value should not be blank.', $form->getErrors(true, true)->offsetGet(0)->getMessage());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content,
            [
                'csrf_protection' => false,
            ]
        );
        $form->submit(
            [
                $ctField->getIdentifier() => 'Foo',
            ]
        );
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
    }
}
