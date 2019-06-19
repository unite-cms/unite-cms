<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class DateFieldTypeTest extends FieldTypeTestCase
{
    public function testDateTypeFieldTypeWithEmptySettings()
    {
        // Date Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('date');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testDateTypeFieldTypeWithInvalidSettings()
    {
        // Date Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('date');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));
        $errors = static::$container->get('validator')->validate($ctField);

        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // test invalid settings
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'foo' => 'baa',
                'not_empty' => 124,
                'min' => 'not a date string',
                'max' => 'not a date string'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(4, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('no_date_value', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('no_date_value', $errors->get(3)->getMessageTemplate());

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

    public function testDateTypeFieldTypeWithInvalidMinMaxRangeSettings()
    {
        // Date Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('date');

        // test min date greater than max date
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'min' => '2019-01-01',
                'max' => '2018-06-20'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('min_greater_than_max', $errors->get(0)->getMessageTemplate());
    }

    public function testDateTypeFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('date');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => '2018-05-24',
                'not_empty' => true,
                'form_group' => 'foo',
                'min' => '2018-05-20',
                'max' => '2018-05-28',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testFormDataTransformers() {

        $ctField = $this->createContentTypeField('date');

        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => '2018-05-24',
        ]);

        $this->assertEquals('2018-05-24', $form->getData()[$ctField->getIdentifier()]);

        $content->setData([
            $ctField->getIdentifier() => '2012-01-01',
        ]);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $this->assertEquals('2012-01-01', $form->get($ctField->getIdentifier())->getData());
    }

    public function testContentFormBuild() {

        $ctField = $this->createContentTypeField('date');

        $ctField->setIdentifier('f1');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('ct1');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => '2012-01-01',
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

        $this->assertEquals('2012-01-01', $root->vars['value']);
        $this->assertEquals('blabla', $root->vars['description']);

    }
}
