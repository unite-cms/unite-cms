<?php

namespace UniteCMS\WysiwygFieldBundle\Tests;

use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;

class VariantsFieldTypeTest extends FieldTypeTestCase
{

    public function testInvalidSettings()
    {
        // Empty settings are not valid.
        $field = $this->createContentTypeField('variants');
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.variants', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());


        // Additional data is not valid
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'icon' => 'test',
                        ]
                    ],
                    'foo' => 'baa',
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        // Invalid variants format
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => 'foo',
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.variants', $errors->get(0)->getPropertyPath());
        $this->assertEquals('variantsfield.not_an_array', $errors->get(0)->getMessageTemplate());

        // Empty variants are not valid
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.variants', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testVariantSettings()
    {
        // Variants must have a title, an identifier, an optional icon and a fields array.
        $field = $this->createContentTypeField('variants');
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'foo' => 'faa',
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(4, $errors);
        $this->assertEquals('settings.variants[0].foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.variants[0].title', $errors->get(1)->getPropertyPath());
        $this->assertEquals('required', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('settings.variants[0].identifier', $errors->get(2)->getPropertyPath());
        $this->assertEquals('required', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('settings.variants[0].fields', $errors->get(3)->getPropertyPath());
        $this->assertEquals('required', $errors->get(3)->getMessageTemplate());

        // Define fields not as array
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'any',
                            'fields' => 'foo',
                            'icon' => 'test',
                            'description' => 'Foo',
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.variants[0].fields', $errors->get(0)->getPropertyPath());
        $this->assertEquals('variantsfield.not_an_array', $errors->get(0)->getMessageTemplate());

        // Use reserved identifier
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'type',
                            'fields' => []
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.variants[0].identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('reserved_identifier', $errors->get(0)->getMessageTemplate());

        // Two variants cannot have the same identifier
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'fields' => [],
                        ],
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'fields' => [],
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.variants[1].identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('identifier_already_taken', $errors->get(0)->getMessageTemplate());

        // Reserved identifier in same variant.
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'any',
                            'fields' => [
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'text'],
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'text']
                            ],
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(2, $errors);
        $this->assertEquals('settings.variants[0].fields[0].identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('identifier_already_taken', $errors->get(0)->getMessageTemplate());

        // Reserved identifier in different variants.
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'fields' => [
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'text'],
                            ],
                        ],
                        [
                            'title' => 'Baa',
                            'identifier' => 'baa',
                            'fields' => [
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'text'],
                            ],
                        ]
                    ],
                ]
            )
        );
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        // Check, that nested field settings will get validated.
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'baa',
                            'fields' => [
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'choices'],
                                ['title' => 'Baa', 'identifier' => 'baa', 'type' => 'choice'],
                            ],
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(2, $errors);
        $this->assertEquals('settings.variants[0].fields[0].type', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_field_type', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.variants[0].fields[1].settings.choices', $errors->get(1)->getPropertyPath());
        $this->assertEquals('required', $errors->get(1)->getMessageTemplate());

        // Define fields as array. All valid now.
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'fields' => [],
                            'icon' => 'test',
                            'description' => 'Foo',
                        ]
                    ],
                ]
            )
        );
        $this->assertCount(0, static::$container->get('validator')->validate($field));
    }

    // TODO: Validate fields
}
