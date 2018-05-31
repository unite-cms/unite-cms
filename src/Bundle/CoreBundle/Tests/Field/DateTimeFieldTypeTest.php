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
        $this->assertCount(0, $this->container->get('validator')->validate($ctField));
    }

    public function testDateTimeTypeFieldTypeWithInvalidSettings()
    {
        // DateTime Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('datetime');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));
        $errors = $this->container->get('validator')->validate($ctField);

        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessage());
    }

    public function testFormDataTransformers() {

        $ctField = $this->createContentTypeField('datetime');

        $content = new Content();
        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => '2018-05-24 12:12:12',
        ]);

        $this->assertEquals('2018-05-24 12:12:12', $form->getData()[$ctField->getIdentifier()]);

        $content->setData([
            $ctField->getIdentifier() => '2012-01-01 10:10:10',
        ]);

        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $this->assertEquals('2012-01-01 10:10:10', $form->get($ctField->getIdentifier())->getData());
    }
}
