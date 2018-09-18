<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;

class LinkFieldTypeTest extends FieldTypeTestCase
{
    public function testLinkFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('link');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testLinkFieldTypeWithInvalidSettings()
    {
        $ctField = $this->createContentTypeField('link');
        
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => 123,
                'target_widget' => 'baa',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('noboolean_value', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
    }

    public function testLinkFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('link');

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => false,
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals(true, $options['title_widget']);
        $this->assertEquals(false, $options['target_widget']);

    }

    public function testLinkFieldTypeDataTransformers() 
    {

        $ctField = $this->createContentTypeField('link');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => true,
            ]
        ));

        $content = new Content();
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'url' => 'http://www.orf.at',
            'target' => '_blank',
            'title' => 'Serwas'
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertEquals($submit_data, (array) json_decode($form->getData()[$ctField->getIdentifier()]));

    }
}