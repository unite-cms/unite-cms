<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\Types\TimeFieldType;

class TimeFieldTypeTest extends FieldTypeTestCase
{
    public function testTimeTypeFieldTypeWithEmptySettings()
    {
        // Time Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('time');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testTimeTypeFieldTypeWithInvalidSettings()
    {
        // Time Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('time');
        $ctField->setSettings(new FieldableFieldSettings(['baa' => 'foo']));
        $errors = static::$container->get('validator')->validate($ctField);

        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // test invalid settings
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'bar' => 'foo',
                'not_empty' => 124
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());

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

    public function testTimeTypeFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('time');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => '08:30:15',
                'not_empty' => true,
                'form_group' => 'foo',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => 'now',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $content = new Content();
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);
        $this->assertEquals((new \DateTime('now'))->format(TimeFieldType::DATE_FORMAT), $form->getData()[$ctField->getIdentifier()]);
    }

    public function testFormDataTransformers() {

        $ctField = $this->createContentTypeField('time');

        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => '08:15', // Form input value does not have seconds
        ]);

        $this->assertEquals('08:15:00', $form->getData()[$ctField->getIdentifier()]);

        $content->setData([
            $ctField->getIdentifier() => '09:30:45',
        ]);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $this->assertEquals('09:30:45', $form->get($ctField->getIdentifier())->getData());
    }

    public function testContentFormBuild() {

        $ctField = $this->createContentTypeField('time');

        $ctField->setIdentifier('f1');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('ct1');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => '09:30:45',
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

        $this->assertEquals('09:30', $root->vars['value']); // Eliminate seconds after form build
        $this->assertEquals('blabla', $root->vars['description']);

    }
}
