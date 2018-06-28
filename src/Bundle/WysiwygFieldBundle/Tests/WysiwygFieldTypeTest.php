<?php

namespace UniteCMS\WysiwygFieldBundle\Tests;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\WysiwygFieldBundle\Field\Types\WysiwygFieldType;

class WysiwygFieldTypeTest extends FieldTypeTestCase
{

    public function testAllowedFieldSettings()
    {
        // Empty settings are valid.
        $field = $this->createContentTypeField('wysiwyg');
        $this->assertCount(0, static::$container->get('validator')->validate($field));


        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => ['bold'],
                    'heading' => ['p'],
                    'placeholder' => 'foo',
                    'foo' => 'baa',
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
    }

    public function testAllowedToolbarOptions()
    {
        $field = $this->createContentTypeField('wysiwyg');

        // Toolbar must be an array
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => 'foo']));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.not_an_array', $errors->get(0)->getMessageTemplate());

        // Fields can be only be set as direct option. There are no groups.
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => [['bold']]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.not_an_array', $errors->get(0)->getMessageTemplate());

        // Only defined options can be set.
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => ['foo']]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.unknown_option', $errors->get(0)->getMessageTemplate());
    }

    public function testAllowedHeadingOptions()
    {
        $field = $this->createContentTypeField('wysiwyg');

        // Heading must be an array
        $field->setSettings(
            new FieldableFieldSettings(['heading' => 'foo']));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.not_an_array', $errors->get(0)->getMessageTemplate());

        // Fields can be only be set as direct option. There are no groups.
        $field->setSettings(
            new FieldableFieldSettings(['heading' => [['p']]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.not_an_array', $errors->get(0)->getMessageTemplate());

        // Only defined options can be set.
        $field->setSettings(
            new FieldableFieldSettings(['heading' => ['foo']]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.unknown_option', $errors->get(0)->getMessageTemplate());
    }

    public function testSettingPassing()
    {
        $field = $this->createContentTypeField('wysiwyg');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => ['bold', 'italic'],
                    'heading' => ['p', 'h1', 'h2'],
                    'placeholder' => 'foo',
                ]
            )
        );

        $fieldType = new WysiwygFieldType();
        $formOptions = $fieldType->getFormOptions($field);
        $this->assertNotEmpty($formOptions['attr']['data-options']);
        $this->assertEquals([
            'placeholder' => 'foo',
            'toolbar' => ['bold', 'italic'],
            'heading' => ['p', 'h1', 'h2'],
        ], json_decode($formOptions['attr']['data-options'], true));
    }
}
