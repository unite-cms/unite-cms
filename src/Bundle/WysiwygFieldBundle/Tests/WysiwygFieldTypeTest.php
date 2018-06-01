<?php

namespace UniteCMS\WysiwygFieldBundle\Tests;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\WysiwygFieldBundle\Field\Types\WysiwygFieldType;

class WysiwygFieldTypeTest extends FieldTypeTestCase
{

    public function testAllowedFieldSettings()
    {
        $field = $this->createContentTypeField('wysiwyg');
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => ['bold'],
                    'theme' => 'snow',
                    'placeholder' => 'foo',
                    'foo' => 'baa',
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testAllowedToolbarOptions()
    {
        $field = $this->createContentTypeField('wysiwyg');

        // Empty toolbar is not valid
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => []]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        // Toolbar must be an array
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => 'foo']));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_definition', $errors->get(0)->getMessage());

        // Fields can be set as direct toolbar child or in child groups
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => ['bold']]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => [['bold', 'italic']]]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        // Only defined options can be set.
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => ['foo']]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.unknown_toolbar_option', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => [['bold', 'foo']]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.unknown_toolbar_option', $errors->get(0)->getMessage());

        // Some options are nested. They should be validated as well.
        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => [ ['header' => 1], [ [ 'header' => 5 ] ] ]]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => [ ['header' => 7] ]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.unknown_toolbar_option', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(['toolbar' => [[ ['header' => 8] ]]]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.toolbar', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.unknown_toolbar_option', $errors->get(0)->getMessage());

    }

    public function testAllowedTheme()
    {
        $field = $this->createContentTypeField('wysiwyg');

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => ['bold'],
                    'theme' => 'foo',
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.theme', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.unknown_theme', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => ['bold'],
                    'theme' => 'snow',
                ]
            )
        );
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => ['bold'],
                    'theme' => 'bubble',
                ]
            )
        );
        $this->assertCount(0, static::$container->get('validator')->validate($field));
    }

    public function testSettingPassing()
    {
        $field = $this->createContentTypeField('wysiwyg');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'toolbar' => [['bold'], ['italic']],
                    'theme' => 'bubble',
                    'placeholder' => 'foo',
                ]
            )
        );

        $fieldType = new WysiwygFieldType();
        $formOptions = $fieldType->getFormOptions($field);
        $this->assertNotEmpty($formOptions['attr']['data-options']);
        $this->assertEquals([
            'theme' => 'bubble',
            'placeholder' => 'foo',
            'modules' => [
                'toolbar' => [['bold'], ['italic']],
            ],
        ], json_decode($formOptions['attr']['data-options'], true));
    }
}
