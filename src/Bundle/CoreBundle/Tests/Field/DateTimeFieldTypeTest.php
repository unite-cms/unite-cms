<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class DateTimeFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {
        // DateTime Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('datetime');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testDateTimeTypeFieldTypeWithInvalidSettings()
    {
        // DateTime Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('datetime');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));
        $errors = static::$container->get('validator')->validate($ctField);

        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'foo' => 'baa',
                'min' => 'not a datetime string',
                'max' => 'not a datetime string',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(3, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('no_datetime_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('no_datetime_value', $errors->get(2)->getMessageTemplate());

        // test wrong initial data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => '15.67.98877'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('no_datetime_value', $errors->get(0)->getMessageTemplate());
    }

    public function testDateTimeTypeFieldTypeWithInvalidMinMaxRangeSettings()
    {
        // Date Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('datetime');

        // test min date greater than max date
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'min' => '2019-01-01T00:00',
                'max' => '2018-06-20T00:00'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('min_greater_than_max', $errors->get(0)->getMessageTemplate());
    }

    public function testDateTimeTypeFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('datetime');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => '2018-05-24T10:10',
                'not_empty' => true,
                'form_group' => 'foo',
                'min' => '2018-05-20T10:10',
                'max' => '2018-05-28T10:10',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testFormDataTransformers() {

        $ctField = $this->createContentTypeField('datetime');

        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => '2018-05-24 12:12:12',
        ]);

        $this->assertEquals('2018-05-24 12:12:12', $form->getData()[$ctField->getIdentifier()]);

        $content->setData([
            $ctField->getIdentifier() => '2012-01-01 10:10:10',
        ]);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $this->assertEquals('2012-01-01 10:10:10', $form->get($ctField->getIdentifier())->getData());
    }
}
