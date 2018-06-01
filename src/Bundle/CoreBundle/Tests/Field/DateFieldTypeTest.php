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
        $this->assertCount(0, $this->container->get('validator')->validate($ctField));
    }

    public function testDateTypeFieldTypeWithInvalidSettings()
    {
        // Date Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('date');
        $ctField->setSettings(new FieldableFieldSettings(['foo' => 'baa']));
        $errors = $this->container->get('validator')->validate($ctField);

        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
    }

    public function testFormDataTransformers() {

        $ctField = $this->createContentTypeField('date');

        $content = new Content();
        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => '2018-05-24',
        ]);

        $this->assertEquals('2018-05-24', $form->getData()[$ctField->getIdentifier()]);

        $content->setData([
            $ctField->getIdentifier() => '2012-01-01',
        ]);

        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $this->assertEquals('2012-01-01', $form->get($ctField->getIdentifier())->getData());
    }
}
