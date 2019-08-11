<?php

namespace UniteCMS\VariantsFieldBundle\Tests;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;

class VariantsFieldTypeTest extends FieldTypeTestCase
{

    public function testValidateInvalidSettings()
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

        // Not_empty must be a boolean value,
        $field->setSettings(new FieldableFieldSettings([
                    'not_empty' => 'fpp',
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'any',
                            'fields' => 'foo',
                            'icon' => 'test',
                            'settings' => [
                                'description' => 'Foo',
                            ],
                        ]
                    ],
                ]
            )
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('noboolean_value', $errors->get(0)->getMessageTemplate());
    }

    public function testValidateVariantSettings()
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
                            'settings' => [
                                'description' => 'Foo',
                            ],
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

        // test more nested variant
        $field->setSettings(
            new FieldableFieldSettings([
                'variants' => [
                    [
                        "title" => "V1",
                        "identifier" => "variant_1",
                        "fields"=> [
                            ["title" => "Text", "identifier" => "field_text", "type" => "text" ]
                        ]
                    ],
                    [
                        'title' => 'V2',
                        'identifier' => 'variant_2',
                        'fields' => [
                            [
                                "title" => "Collection",
                                "identifier" => "collection",
                                "type" => "collection",
                                "settings" => [
                                    "fields" => [
                                        [
                                            "title" => "image",
                                            "identifier" => "image",
                                            "type" => "image",
                                            "settings" => [
                                                "bucket" => [
                                                    "path" => "image",
                                                    "key" =>  "XDSS",
                                                    "secret" => "FGHTEDDD"
                                                ]
                                            ]
                                        ],
                                        [
                                            'title' => 'Foo',
                                            'identifier' => 'field-text',
                                            'type' => 'text'
                                        ],
                                    ]
                                ]
                            ]
                        ],
                    ]
                ]
            ])
        );
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(3, $errors);
        $this->assertEquals('settings.variants[1].fields[1].settings.fields[image].settings.bucket.endpoint', $errors->get(0)->getPropertyPath());
        $this->assertEquals('settings.variants[1].fields[1].settings.fields[image].settings.bucket.bucket', $errors->get(1)->getPropertyPath());
        $this->assertEquals('settings.variants[1].fields[1].settings.fields[field-text].identifier', $errors->get(2)->getPropertyPath());

        // Reserved identifier in different variants.
        $field->setSettings(new FieldableFieldSettings([
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'fields' => [
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'text', 'permissions' => ['list field' => 'false', 'view field' => 'false', 'update field' => 'false']],
                            ],
                        ],
                        [
                            'title' => 'Baa',
                            'identifier' => 'baa',
                            'fields' => [
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'text', 'permissions' => ['list field' => 'false', 'view field' => 'false', 'update field' => 'false']],
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
                                ['title' => 'Foo', 'identifier' => 'foo', 'type' => 'anyunknown'],
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
                            'settings' => [
                                'description' => 'Foo',
                            ],
                        ]
                    ],
                    'form_group' => "Group 1",
                    'not_empty' => true,
                ]
            )
        );
        $this->assertCount(0, static::$container->get('validator')->validate($field));
    }

    public function testBuildFormAndValidateData() {

        // Create field and save it to the database.
        $field = $this->createContentTypeField('variants');
        $field->setSettings(new FieldableFieldSettings([
                    'not_empty' => true,
                    'variants' => [
                        [
                            'title' => 'Foo',
                            'identifier' => 'foo',
                            'fields' => [
                                [
                                    'title' => 'Text',
                                    'identifier' => 'text',
                                    'type' => 'text',
                                ],
                                [
                                    'title' => 'Hidden',
                                    'identifier' => 'hidden',
                                    'type' => 'text',
                                    'permissions' => [
                                        'list field' => 'false',
                                        'view field' => 'false',
                                        'update field' => 'false',
                                    ]
                                ],
                                [
                                    'title' => 'Default',
                                    'identifier' => 'default',
                                    'type' => 'text',
                                    'settings' => [
                                        'default' => 'my_default_value',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'title' => 'Baa',
                            'identifier' => 'baa',
                            'settings' => [
                                'description' => 'Fooo',
                            ],
                            'icon' => 'any',
                            'fields' => [
                                [
                                    'title' => 'Ref',
                                    'identifier' => 'ref',
                                    'type' => 'reference',
                                    'settings' => [
                                        'content_type' => $field->getContentType()->getIdentifier(),
                                        'domain' => $field->getContentType()->getDomain()->getIdentifier(),
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            )
        );

        $this->em->persist(
            $field->getContentType()->getDomain()->getOrganization()
        );
        $this->em->persist($field->getContentType()->getDomain());
        $this->em->persist($field->getContentType());
        $this->em->flush();

        $this->em->refresh($field->getContentType()->getDomain());
        $this->em->refresh($field->getContentType());
        $this->em->refresh($field);

        // Inject org and domain into unite.cms.manager
        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'organization');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), $field->getContentType()->getDomain()->getOrganization());

        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), $field->getContentType()->getDomain());

        // Fake user
        $user = new User();
        $domainMember = new DomainMember();
        $domainMember->setDomain($field->getContentType()->getDomain())->setDomainMemberType($field->getContentType()->getDomain()->getDomainMemberTypes()->first());
        $user->addDomain($domainMember);
        $user->setRoles([User::ROLE_USER]);
        static::$container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $this->assertCount(0, static::$container->get('validator')->validate($field));

        // Build form for this field.
        $content = new Content();
        $content->setContentType($field->getContentType());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);
        $formView = $form->createView();

        // Assert type and 2 variants child of variants form type
        $root = $formView->getIterator()->current();
        $this->assertCount(3, $root->children);

        $this->assertEquals('Foo', $root->children['type']->vars['choices'][0]->label);
        $this->assertEquals('foo', $root->children['type']->vars['choices'][0]->value);
        $this->assertEquals(['icon' => '', 'description' => ''], $root->children['type']->vars['choices'][0]->attr);
        $this->assertEquals('Baa', $root->children['type']->vars['choices'][1]->label);
        $this->assertEquals('baa', $root->children['type']->vars['choices'][1]->value);
        $this->assertEquals(['icon' => 'any', 'description' => 'Fooo'], $root->children['type']->vars['choices'][1]->attr);
        $this->assertEquals(['data-variant-title', 'data-variant-icon', 'data-graphql-query-mapper'], array_keys($root->children['foo']->vars['attr']));
        $this->assertEquals('Foo', $root->children['foo']->vars['attr']['data-variant-title']);
        $this->assertEquals(['data-variant-title', 'data-variant-icon', 'data-graphql-query-mapper'], array_keys($root->children['baa']->vars['attr']));
        $this->assertEquals('Baa', $root->children['baa']->vars['attr']['data-variant-title']);
        $this->assertEquals('any', $root->children['baa']->vars['attr']['data-variant-icon']);

        // Check, that child form fields get rendered.
        $this->assertCount(2, $root->children['foo']->children);
        $this->assertContains('text', $root->children['foo']->children['text']->vars['block_prefixes']);
        $this->assertContains('text', $root->children['foo']->children['default']->vars['block_prefixes']);
        $this->assertCount(1, $root->children['baa']->children);
        $this->assertContains('unite_cms_core_reference', $root->children['baa']->children['ref']->vars['block_prefixes']);

        // Check that default value get passed to the form
        $this->assertEquals('my_default_value', $root->children['foo']->children['default']->vars['value']);

        // Try to submit invalid type data
        $form->submit([
            $field->getIdentifier() => [
                'type' => 'any',
            ]
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $errors = $form->getErrors(true, true);
        $this->assertCount(1, $errors);
        $this->assertEquals('type', $errors->current()->getOrigin()->getName());
        $this->assertEquals('This value is not valid.', $errors->current()->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($content->getContentType(), $content, [
                'csrf_protection' => false,
                'validation_groups' => ['Default']
            ]);

        // Try to submit nested invalid data
        $form->submit([
            $field->getIdentifier() => [
                'type' => 'baa',
                'baa' => [
                    'ref' => [
                        'domain' => 'foo',
                        'content' => 'foo',
                    ]
                ],
                'foo' => [
                    'text' => 'baa',
                ]
            ]
        ]);

        $content->setData($form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data[' . $field->getIdentifier() . '][baa][ref]', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        // Try to submit valid data and check, that content was updated correctly.

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);

        // Try to submit nested valid data
        $form->submit([
            $field->getIdentifier() => [
                'type' => 'foo',
                'foo' => [
                    'text' => 'This is my text'
                ]
            ]
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        $content->setData($form->getData());
        $this->assertEquals([
            $field->getIdentifier() => [
                'type' => 'foo',
                'foo' => [
                    'text' => 'This is my text',
                    'default' => null,
                ]
            ],
        ],$content->getData());

        // Try to submit empty data
        $content->setData([]);

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);

        $form->submit([]);
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content, $form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data[' . $field->getIdentifier() . ']', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);
        $form->submit([
            $field->getIdentifier() => null,
        ]);
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content, $form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data[' . $field->getIdentifier() . ']', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);
        $form->submit([
            $field->getIdentifier() => [],
        ]);
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content, $form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data[' . $field->getIdentifier() . ']', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);
        $form->submit([
            $field->getIdentifier() => [
                'type' => null,
            ],
        ]);
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content, $form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data[' . $field->getIdentifier() . ']', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);
        $form->submit([
            $field->getIdentifier() => [
                'type' => '',
            ],
        ]);
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content, $form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data[' . $field->getIdentifier() . ']', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')
            ->createForm($field->getContentType(), $content, ['csrf_protection' => false]);
        $form->submit([
            $field->getIdentifier() => [
                'type' => 'foo',
            ],
        ]);
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        static::$container->get('unite.cms.fieldable_form_builder')->assignDataToFieldableContent($content, $form->getData());
        $errors = static::$container->get('validator')->validate($content);
        $this->assertCount(0, $errors);
    }
}
