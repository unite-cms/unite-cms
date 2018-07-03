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

        // Fields can be only be set as direct option or as full heading definition
        $field->setSettings(
            new FieldableFieldSettings(['heading' => [['p']]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.invalid_heading_definition', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(['heading' => [
                [
                    'view' => ['name' => 'p', 'classes' => 'missing-view'],
                ]
            ]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.invalid_heading_definition', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(['heading' => [
                [
                    'view' => 'p',
                    'model' => 'myP',
                    'title' => 'la',
                    'class' => 'my-editor-class',
                ]
            ]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.invalid_heading_definition', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(['heading' => [
                [
                    'view' => ['name' => 'unknown', 'classes' => 'fooba'],
                    'model' => 'myP',
                    'title' => 'la',
                    'class' => 'my-editor-class',
                ]
            ]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading.unknown', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.invalid_heading_definition', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(['heading' => [
                [
                    'view' => ['name' => 'p', 'additional_attribute' => 'foo'],
                    'model' => 'myP',
                    'title' => 'la',
                    'class' => 'my-editor-class',
                ]
            ]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading.p', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.invalid_heading_definition', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(['heading' => [
                [
                    'view' => ['name' => 'p'],
                    'model' => 'myP',
                    'additional_attribute' => 'foo',
                ]
            ]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.heading.p', $errors->get(0)->getPropertyPath());
        $this->assertEquals('wysiwygfield.invalid_heading_definition', $errors->get(0)->getMessageTemplate());

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
                    'heading' => ['p', 'h1', 'h2', 'code', [ 'view' => ['name' => 'p', 'classes' => 'fancy'], 'model' => 'fancyParagraph', 'title' => 'Fancy Pargraph' ]],
                    'placeholder' => 'foo',
                ]
            )
        );

        $fieldType = new WysiwygFieldType();
        $formOptions = $fieldType->getFormOptions($field);
        $this->assertNotEmpty($formOptions['attr']['data-options']);
        $this->assertEquals([
            'placeholder' => 'foo',
            'toolbar' => ['heading', '|', 'bold', 'italic'],
            'heading' => [
                [ 'view' => 'p', 'model' => 'paragraph', 'title' => 'Paragraph', 'class' => 'ck-heading_paragraph' ],
                [ 'view' => 'h1', 'model' => 'heading1', 'title' => 'Heading 1', 'class' => 'ck-heading_heading1' ],
                [ 'view' => 'h2', 'model' => 'heading2', 'title' => 'Heading 2', 'class' => 'ck-heading_heading2' ],
                [ 'view' => 'code', 'model' => 'code', 'title' => 'Code' ],
                [
                    'view' => [
                        'name' => 'p',
                        'classes' => 'fancy'
                    ],
                    'model' => 'fancyParagraph',
                    'title' => 'Fancy Pargraph',
                    'class' => 'ck-heading_paragraph'
                ]
            ],
        ], json_decode($formOptions['attr']['data-options'], true));
    }
}
