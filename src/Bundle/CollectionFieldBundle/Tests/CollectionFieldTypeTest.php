<?php

namespace UniteCMS\CollectionFieldBundle\Tests;

use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiClient;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;

class CollectionFieldTypeTest extends FieldTypeTestCase
{
    public function testAllowedFieldSettings()
    {
        $field = $this->createContentTypeField('collection');
        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [],
                    'min_rows' => 0,
                    'max_rows' => 100,
                    'foo' => 'baa',
                ]
            )
        );
        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [],
                    'min_rows' => 0,
                    'max_rows' => 100,
                ]
            )
        );

        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(0, $errors);
    }

    public function testAddingEmptyCollectionFieldType()
    {

        $field = $this->createContentTypeField('collection');

        $content = new Content();
        $content->setContentType($field->getContentType());

        // Try to validate empty collection field definitions.
        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [],
                ]
            )
        );

        // Try to validate collection without fields.
        $this->assertCount(0, $this->container->get('validator')->validate($field));

        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->has($field->getIdentifier()));
        $this->assertEquals($field->getTitle(), $form->get($field->getIdentifier())->getConfig()->getOption('label'));
        $csrf_token = $this->container->get('security.csrf.token_manager')->getToken($form->getName());
        $formData = [
            '_token' => $csrf_token->getValue(),
        ];

        // Submitting empty data should be valid.
        $form->submit($formData);
        $this->assertTrue($form->isValid());

        // Submitting sub field data should be valid since we auto-delete empty rows, but content data must be empty.
        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $form->submit(
            [
                '_token' => $csrf_token->getValue(),
                $field->getIdentifier() => [['foo' => 'baa']],
            ]
        );
        $this->assertTrue($form->isValid());
        $this->assertEmpty($form->getData()[$field->getIdentifier()]);
    }

    public function testAddingCollectionFieldTypeWithFields()
    {
        $field = $this->createContentTypeField('collection');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                        ],
                    ],
                ]
            )
        );

        $content = new Content();
        $content->setContentType($field->getContentType());

        // Try to validate collection with sub field definitions.
        $this->assertCount(0, $this->container->get('validator')->validate($field));

        // Submitting sub field data should work, for the given fields.
        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $csrf_token = $this->container->get('security.csrf.token_manager')->getToken($form->getName());
        $form->submit(
            [
                '_token' => $csrf_token->getValue(),
                $field->getIdentifier() => [['f1' => 'value']],
            ]
        );
        $this->assertTrue($form->isValid());
        $this->assertNotEmpty($form->getData());
        $this->assertEquals([$field->getIdentifier() => [['f1' => 'value']]], $form->getData());
    }

    public function testGettingGraphQLData()
    {

        $field = $this->createContentTypeField('collection');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                        ],
                        [
                            'title' => 'Nested Field 1',
                            'identifier' => 'n1',
                            'type' => 'collection',
                            'settings' => [
                                'fields' => [
                                    [
                                        'title' => 'Nested Field 2',
                                        'identifier' => 'n2',
                                        'type' => 'collection',
                                        'settings' => [
                                            'fields' => [
                                                [
                                                    'title' => 'Sub Field 2',
                                                    'identifier' => 'f2',
                                                    'type' => 'text',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );
        $this->em->persist($field->getContentType()->getDomain()->getOrganization());
        $this->em->persist($field->getContentType()->getDomain());
        $this->em->persist($field->getContentType());
        $this->em->flush();

        $this->em->refresh($field->getContentType()->getDomain());
        $this->em->refresh($field->getContentType());
        $this->em->refresh($field);

        // Inject created domain into untied.cms.manager.
        $d = new \ReflectionProperty($this->container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue($this->container->get('unite.cms.manager'), $field->getContentType()->getDomain());

        $key = ucfirst($field->getContentType()->getIdentifier()).'Content';
        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            $key,
            $field->getContentType()->getDomain()
        );
        $this->assertInstanceOf(ObjectType::class, $type);

        // Check nested collection field structure.
        $this->assertArrayHasKey('f1', $type->getFields());
        $this->assertArrayHasKey('f1', $type->getField('f1')->getType()->getWrappedType()->getFields());
        $this->assertArrayHasKey('n1', $type->getField('f1')->getType()->getWrappedType()->getFields());
        $this->assertArrayHasKey(
            'n2',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getFields()
        );
        $this->assertArrayHasKey(
            'f2',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'n2'
            )->getType()->getWrappedType()->getFields()
        );

        $this->assertEquals('Ct1F1CollectionField', $type->getField('f1')->getType()->name);
        $this->assertEquals('Ct1F1CollectionFieldRow', $type->getField('f1')->getType()->getWrappedType()->name);
        $this->assertEquals(
            'Ct1F1N1CollectionField',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->name
        );
        $this->assertEquals(
            'Ct1F1N1CollectionFieldRow',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->name
        );
        $this->assertEquals(
            'Ct1F1N1N2CollectionField',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'n2'
            )->getType()->name
        );
        $this->assertEquals(
            'Ct1F1N1N2CollectionFieldRow',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'n2'
            )->getType()->getWrappedType()->name
        );
        $this->assertEquals(
            'String',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'n2'
            )->getType()->getWrappedType()->getField('f2')->getType()->name
        );
    }

    public function testWritingGraphQLData()
    {

        $field = $this->createContentTypeField('collection');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                        ],
                        [
                            'title' => 'Nested Field 1',
                            'identifier' => 'n1',
                            'type' => 'collection',
                            'settings' => [
                                'fields' => [
                                    [
                                        'title' => 'Nested Field 2',
                                        'identifier' => 'n2',
                                        'type' => 'collection',
                                        'settings' => [
                                            'fields' => [
                                                [
                                                    'title' => 'Sub Field 2',
                                                    'identifier' => 'f2',
                                                    'type' => 'text',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
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

        // Inject created domain into untied.cms.manager.
        $d = new \ReflectionProperty(
            $this->container->get('unite.cms.manager'), 'domain'
        );
        $d->setAccessible(true);
        $d->setValue(
            $this->container->get('unite.cms.manager'),
            $field->getContentType()->getDomain()
        );
        $domain = $field->getContentType()->getDomain();

        // In this test, we don't care about access checking.
        $admin = new ApiClient();
        $admin->setDomain($field->getContentType()->getDomain());
        $admin->setRoles([Domain::ROLE_ADMINISTRATOR]);
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, null, 'api', $admin->getRoles())
        );

        // Create GraphQL Schema
        $schemaTypeManager = $this->container->get(
            'unite.cms.graphql.schema_type_manager'
        );

        $schema = new Schema(
            [
                'query' => $schemaTypeManager->getSchemaType('Query'),
                'mutation' => $schemaTypeManager->getSchemaType('Mutation'),
                'typeLoader' => function ($name) use ($schemaTypeManager, $domain) {
                    return $schemaTypeManager->getSchemaType($name, $domain);
                },
            ]
        );

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
      createCt1(
        data: {
          f1: [
            {},
            {
              f1: "Foo",
              n1: [
                {
                  n2: [
                    { f2: "Baa" }
                  ]
                }
              ]
            }
          ]
        }
      ) {
        id,
        f1 {
          f1,
          n1 {
            n2 {
              f2
            }
          }
        }
       }
    }'
        );
        $result = json_decode(json_encode($result->toArray()));
        $this->assertNotEmpty($result->data->createCt1->id);
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')
            ->find($result->data->createCt1->id);
        $this->assertNotNull($content);
        $this->assertNotNull($result->data->createCt1->f1[0]);
        $this->assertEquals('Foo', $result->data->createCt1->f1[1]->f1);
        $this->assertEquals(
            'Baa',
            $result->data->createCt1->f1[1]->n1[0]->n2[0]->f2
        );
        $this->assertEquals('Foo', $content->getData()['f1'][1]['f1']);
    }

    public function testWritingGraphQLDataViaMainFirewallWithCSRFProtection()
    {

        $field = $this->createContentTypeField('collection');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                        ],
                        [
                            'title' => 'Nested Field 1',
                            'identifier' => 'n1',
                            'type' => 'collection',
                            'settings' => [
                                'fields' => [
                                    [
                                        'title' => 'Nested Field 2',
                                        'identifier' => 'n2',
                                        'type' => 'collection',
                                        'settings' => [
                                            'fields' => [
                                                [
                                                    'title' => 'Sub Field 2',
                                                    'identifier' => 'f2',
                                                    'type' => 'text',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
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

        // Inject created domain into untied.cms.manager.
        $d = new \ReflectionProperty(
            $this->container->get('unite.cms.manager'), 'domain'
        );
        $d->setAccessible(true);
        $d->setValue(
            $this->container->get('unite.cms.manager'),
            $field->getContentType()->getDomain()
        );
        $domain = $field->getContentType()->getDomain();

        // In this test, we don't care about access checking.
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, null, 'main', $admin->getRoles())
        );

        // Create GraphQL Schema
        $schemaTypeManager = $this->container->get(
            'unite.cms.graphql.schema_type_manager'
        );

        $schema = new Schema(
            [
                'query' => $schemaTypeManager->getSchemaType('Query'),
                'mutation' => $schemaTypeManager->getSchemaType('Mutation'),
                'typeLoader' => function ($name) use ($schemaTypeManager, $domain) {
                    return $schemaTypeManager->getSchemaType($name, $domain);
                },
            ]
        );

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
      createCt1(
        data: {
          f1: [
            {},
            {
              f1: "Foo",
              n1: [
                {
                  n2: [
                    { f2: "Baa" }
                  ]
                }
              ]
            }
          ]
        }
      ) {
        id,
        f1 {
          f1,
          n1 {
            n2 {
              f2
            }
          }
        }
       }
    }',
            null,
            [
                'csrf_token' => $this->container->get('security.csrf.token_manager')
                    ->getToken(StringUtil::fqcnToBlockPrefix(FieldableFormType::class))
                    ->getValue(),
            ]
        );
        $result = json_decode(json_encode($result->toArray()));
        $this->assertNotEmpty($result->data->createCt1->id);
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')
            ->find($result->data->createCt1->id);
        $this->assertNotNull($content);
        $this->assertNotNull($result->data->createCt1->f1[0]);
        $this->assertEquals('Foo', $result->data->createCt1->f1[1]->f1);
        $this->assertEquals(
            'Baa',
            $result->data->createCt1->f1[1]->n1[0]->n2[0]->f2
        );
        $this->assertEquals('Foo', $content->getData()['f1'][1]['f1']);
    }

    public function testValidatingContent()
    {
        $field = $this->createContentTypeField('collection');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'min_rows' => 1,
                    'max_rows' => 4,
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                        ],
                        [
                            'title' => 'Nested Field 1',
                            'identifier' => 'n1',
                            'type' => 'collection',
                            'settings' => [
                                'fields' => [
                                    [
                                        'title' => 'Nested Field 2',
                                        'identifier' => 'n2',
                                        'type' => 'collection',
                                        'settings' => [
                                            'fields' => [
                                                [
                                                    'title' => 'Sub Field 2',
                                                    'identifier' => 'f2',
                                                    'type' => 'reference',
                                                    'settings' => [
                                                        'domain' => 'foo',
                                                        'content_type' => 'baa',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        // Inject created domain into untied.cms.manager.
        $d = new \ReflectionProperty($this->container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue($this->container->get('unite.cms.manager'), $field->getContentType()->getDomain());
        $o = new \ReflectionProperty($this->container->get('unite.cms.manager'), 'organization');
        $o->setAccessible(true);
        $o->setValue(
            $this->container->get('unite.cms.manager'),
            $field->getContentType()->getDomain()->getOrganization()
        );

        // Validate min rows.
        $violations = $this->container->get('unite.cms.field_type_manager')->validateFieldData($field, []);
        $this->assertCount(1, $violations);
        $this->assertEquals('['.$field->getIdentifier().']', $violations[0]->getPropertyPath());
        $this->assertEquals('validation.too_few_rows', $violations[0]->getMessage());

        // on DELETE all content is valid.
        $this->assertCount(
            0,
            $this->container->get('unite.cms.field_type_manager')->validateFieldData($field, [], 'DELETE')
        );

        // Validate max rows.
        $violations = $this->container->get('unite.cms.field_type_manager')
            ->validateFieldData(
                $field,
                [
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                ]
            );
        $this->assertCount(1, $violations);
        $this->assertEquals('['.$field->getIdentifier().']', $violations[0]->getPropertyPath());
        $this->assertEquals('validation.too_many_rows', $violations[0]->getMessage());

        // on DELETE all content is valid.
        $this->assertCount(
            0,
            $this->container->get('unite.cms.field_type_manager')->validateFieldData($field, [], 'DELETE')
        );

        // Validate additional data (also nested).
        $violations = $this->container->get('unite.cms.field_type_manager')
            ->validateFieldData(
                $field,
                [
                    ['f1' => 'baa'],
                    ['foo' => 'baa'],
                    [
                        'n1' => [
                            [
                                'n2' => [
                                    [
                                        'f2' => [
                                            'domain' => 'foo',
                                            'content_type' => 'baa',
                                            'content' => 'any',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'n1' => [
                            [
                                'n2' => [
                                    ['f2' => ['domain' => 'foo'], 'foo' => 'baa',],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        $this->assertCount(4, $violations);
        $this->assertEquals(
            $field->getEntity()->getIdentifierPath('.').'.'.$field->getIdentifier().'.foo',
            $violations[0]->getPropertyPath()
        );
        $this->assertEquals('validation.additional_data', $violations[0]->getMessage());
        $this->assertEquals('[f2]', $violations[1]->getPropertyPath());
        $this->assertEquals('validation.wrong_definition', $violations[1]->getMessage());
        $this->assertEquals('[f2]', $violations[2]->getPropertyPath());
        $this->assertEquals('validation.missing_definition', $violations[2]->getMessage());
        $this->assertEquals(
            $field->getEntity()->getIdentifierPath('.').'.'.$field->getIdentifier().'.n1.n2.foo',
            $violations[3]->getPropertyPath()
        );
        $this->assertEquals('validation.additional_data', $violations[3]->getMessage());

        // on DELETE all content is valid.
        $this->assertCount(
            0,
            $this->container->get('unite.cms.field_type_manager')->validateFieldData($field, [], 'DELETE')
        );
    }

    public function testFormBuilding()
    {

        $field = $this->createContentTypeField('collection');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'min_rows' => 1,
                    'max_rows' => 4,
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                        ],
                        [
                            'title' => 'Nested Field 1',
                            'identifier' => 'n1',
                            'type' => 'collection',
                            'settings' => [
                                'fields' => [
                                    [
                                        'title' => 'Nested Field 2',
                                        'identifier' => 'n2',
                                        'type' => 'collection',
                                        'settings' => [
                                            'fields' => [
                                                [
                                                    'title' => 'Sub Field 2',
                                                    'identifier' => 'f2',
                                                    'type' => 'text',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );
        $content = new Content();
        $content->setData(
            [
                $field->getIdentifier() => [
                    ['f1' => 'baa'],
                    ['n1' => [['n2' => [['f2' => 'foo',]]]],],
                ],
            ]
        )->setContentType($field->getContentType());
        $form = $this->container->get('unite.cms.fieldable_form_builder')
            ->createForm(
                $field->getContentType(),
                $content
            );
        $formView = $form->createView();

        // Check root collection field.
        $root = $formView->getIterator()->current();
        $this->assertEquals('unite-cms-collection-field', $root->vars['tag']);

        // First Row
        $row1 = array_shift($root->children);
        $row2 = array_shift($root->children);

        // Row 1 field 1
        $row1F1 = array_shift($row1->children);
        $this->assertEquals('f1', $row1F1->vars['name']);
        $this->assertEquals('baa', $row1F1->vars['value']);

        // Row 2 field 1
        $row2F1 = array_shift($row2->children);
        $this->assertEquals('f1', $row2F1->vars['name']);
        $this->assertEquals('', $row2F1->vars['value']);

        // Row 2 nested field 1
        $row2N1 = array_shift($row2->children);
        $this->assertEquals('n1', $row2N1->vars['name']);
        $this->assertEquals(
            'unite-cms-collection-field',
            $row2N1->vars['tag']
        );

        // Row 2 nested field 1 nested field 2
        $row2N1Row1 = array_shift($row2N1->children);
        $row2N1Row1N2 = array_shift($row2N1Row1->children);
        $this->assertEquals('n2', $row2N1Row1N2->vars['name']);
        $this->assertEquals(
            'unite-cms-collection-field',
            $row2N1Row1N2->vars['tag']
        );

        // Row 2 nested field 1 nested field 2 nested field f2
        $row2N1Row1N2Row1 = array_shift($row2N1Row1N2->children);
        $row2N1Row1N2Row1F2 = array_shift($row2N1Row1N2Row1->children);
        $this->assertEquals('f2', $row2N1Row1N2Row1F2->vars['name']);
        $this->assertEquals('foo', $row2N1Row1N2Row1F2->vars['value']);

    }
}
