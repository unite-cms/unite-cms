<?php

namespace UniteCMS\CollectionFieldBundle\Tests;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Validator\Context\ExecutionContext;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;

class CollectionFieldTypeTest extends FieldTypeTestCase
{
    /**
     * @var User
     */
    protected $user;

    public function setUp()
    {
        parent::setUp();
        $this->user = new User();
        $this->user->setRoles([User::ROLE_USER]);

        $domainMember = new DomainMember();
        $this->user->addDomain($domainMember);

        static::$container->get('security.token_storage')->setToken(new UsernamePasswordToken($this->user, '', 'main', $this->user->getRoles()));
    }

    public function testAllowedFieldSettings()
    {
        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

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
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [],
                    'min_rows' => 0,
                    'max_rows' => 100,
                    'form_group' => "Group 1",
                ]
            )
        );

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(0, $errors);
    }

    public function testAddingEmptyCollectionFieldType()
    {

        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

        $content = new Content();
        $content->setContentType($field->getContentType());

        // Try to validate empty collection field definitions.
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [],
                ]
            )
        );

        // Try to validate collection without fields.
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->has($field->getIdentifier()));
        $this->assertEquals($field->getTitle(), $form->get($field->getIdentifier())->getConfig()->getOption('label'));
        $csrf_token = static::$container->get('security.csrf.token_manager')->getToken($form->getName());
        $formData = [
            '_token' => $csrf_token->getValue(),
        ];

        // Submitting empty data should be valid.
        $form->submit($formData);
        $this->assertTrue($form->isValid());

        // Submitting sub field data should be valid since we auto-delete empty rows, but content data must be empty.
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
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
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        // Submitting sub field data should work, for the given fields.
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $csrf_token = static::$container->get('security.csrf.token_manager')->getToken($form->getName());
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

    public function testAddingCollectionFieldTypeWithFieldAndPermission()
    {
        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'fields' => [
                        [
                            'title' => 'Sub Field 1',
                            'identifier' => 'f1',
                            'type' => 'text',
                            'permissions' => [
                                'list field' => 'false',
                                'view field' => 'true',
                                'update field' => 'true',
                            ],
                        ],
                    ],
                ]
            )
        );

        $content = new Content();
        $content->setContentType($field->getContentType());

        $this->assertCount(0, static::$container->get('validator')->validate($field));
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $csrf_token = static::$container->get('security.csrf.token_manager')->getToken($form->getName());
        $form->submit(
            [
                '_token' => $csrf_token->getValue(),
                $field->getIdentifier() => [['f1' => 'value']],
            ]
        );

        $this->assertEquals([$field->getIdentifier() => []], $form->getData());

        $field->getSettings()->fields[0]['permissions'] = [
            'list field' => 'true',
            'view field' => 'true',
            'update field' => 'false',
        ];

        $this->assertCount(0, static::$container->get('validator')->validate($field));
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $csrf_token = static::$container->get('security.csrf.token_manager')->getToken($form->getName());
        $form->submit(
            [
                '_token' => $csrf_token->getValue(),
                $field->getIdentifier() => [['f1' => 'value']],
            ]
        );

        $this->assertEquals([$field->getIdentifier() => []], $form->getData());

        $field->getSettings()->fields[0]['permissions'] = [
            'list field' => 'true',
            'view field' => 'false',
            'update field' => 'true',
        ];

        $this->assertCount(0, static::$container->get('validator')->validate($field));
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $csrf_token = static::$container->get('security.csrf.token_manager')->getToken($form->getName());
        $form->submit(
            [
                '_token' => $csrf_token->getValue(),
                $field->getIdentifier() => [['f1' => 'value']],
            ]
        );

        $this->assertEquals([$field->getIdentifier() => [['f1' => 'value']]], $form->getData());
    }

    public function testGettingGraphQLData()
    {

        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
                                        'identifier' => 'nested_2',
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
        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), $field->getContentType()->getDomain());

        $key = ucfirst($field->getContentType()->getIdentifier()).'Content';
        $type = static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            $key,
            $field->getContentType()->getDomain()
        );
        $this->assertInstanceOf(ObjectType::class, $type);

        // Check nested collection field structure.
        $this->assertArrayHasKey('f1', $type->getFields());
        $this->assertArrayHasKey('f1', $type->getField('f1')->getType()->getWrappedType()->getFields());
        $this->assertArrayHasKey('n1', $type->getField('f1')->getType()->getWrappedType()->getFields());
        $this->assertArrayHasKey(
            'nested_2',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getFields()
        );
        $this->assertArrayHasKey(
            'f2',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'nested_2'
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
            'Ct1F1N1Nested_2CollectionField',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'nested_2'
            )->getType()->name
        );
        $this->assertEquals(
            'Ct1F1N1Nested_2CollectionFieldRow',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'nested_2'
            )->getType()->getWrappedType()->name
        );
        $this->assertEquals(
            'String',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getField(
                'nested_2'
            )->getType()->getWrappedType()->getField('f2')->getType()->name
        );
    }

    public function testGettingGraphQLDataWithoutPermissions()
    {

        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
                            'permissions' => [
                                'list field' => 'false',
                                'view field' => 'true',
                                'update field' => 'true',
                            ],
                        ],
                        [
                            'title' => 'Nested Field 1',
                            'identifier' => 'n1',
                            'type' => 'collection',
                            'settings' => [
                                'fields' => [
                                    [
                                        'title' => 'Nested Field 2',
                                        'identifier' => 'nested_2',
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
                                        'permissions' => [
                                            'list field' => 'false',
                                            'view field' => 'true',
                                            'update field' => 'true',
                                        ],
                                    ],
                                    [
                                        'title' => '2nd',
                                        'identifier' => 'f_2nd',
                                        'type' => 'text',
                                    ]
                                ],
                            ],
                        ],
                        [
                            'title' => 'Sub Field 3',
                            'identifier' => 'f3',
                            'type' => 'text',
                            'permissions' => [
                                'list field' => 'true',
                                'view field' => 'content.data.f1 == "X"',
                                'update field' => 'true',
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
        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), $field->getContentType()->getDomain());

        $key = ucfirst($field->getContentType()->getIdentifier()).'Content';
        $type = static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            $key,
            $field->getContentType()->getDomain()
        );
        $this->assertInstanceOf(ObjectType::class, $type);

        // Check nested collection field structure.
        $this->assertArrayHasKey('f1', $type->getFields());
        $this->assertArrayNotHasKey('f1', $type->getField('f1')->getType()->getWrappedType()->getFields());
        $this->assertArrayHasKey('n1', $type->getField('f1')->getType()->getWrappedType()->getFields());
        $this->assertArrayNotHasKey(
            'nested_2',
            $type->getField('f1')->getType()->getWrappedType()->getField('n1')->getType()->getWrappedType()->getFields()
        );

        // In this test, we don't care about access checking.
        $admin = new ApiKey();
        $admin->setName('admin_key')->setOrganization($field->getContentType()->getDomain()->getOrganization());
        $domainMember = new DomainMember();
        $domainMember->setDomain($field->getContentType()->getDomain())->setDomainMemberType($field->getContentType()->getDomain()->getDomainMemberTypes()->get('editor'));
        $admin->addDomain($domainMember);
        static::$container->get('security.token_storage')->setToken(
            new PostAuthenticationGuardToken($admin, 'api', [])
        );

        $c1 = new Content();
        $c1->setContentType($field->getContentType());
        $c1->setData([
            'f1' => [
                [
                    'f1' => 'Foo',
                    'f3' => 'Baa',
                ]
            ],
        ]);
        $this->em->persist($c1);
        $this->em->flush();

        $schema = static::$container->get('unite.cms.graphql.schema_type_manager')->createSchema($field->getContentType()->getDomain(), 'Query', 'Mutation');
        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                findCt1 {
                    result {
                        f1 {
                            f3
                        }
                    }
                }
            }'
        );
        $result = json_decode(json_encode($result->toArray()));
        $this->assertNull($result->data->findCt1->result[0]->f1[0]->f3);

        $c2 = new Content();
        $c2->setContentType($field->getContentType());
        $c2->setData([
            'f1' => [
                [
                    'f1' => 'X',
                    'f3' => 'Baa2',
                ]
            ],
        ]);
        $this->em->persist($c2);
        $this->em->flush();

        $schema = static::$container->get('unite.cms.graphql.schema_type_manager')->createSchema($field->getContentType()->getDomain(), 'Query', 'Mutation');
        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                findCt1 {
                    result {
                        f1 {
                            f3
                        }
                    }
                }
            }'
        );
        $result = json_decode(json_encode($result->toArray()));
        $this->assertNull($result->data->findCt1->result[0]->f1[0]->f3);
        $this->assertEquals('Baa2', $result->data->findCt1->result[1]->f1[0]->f3);
    }

    public function testWritingGraphQLData()
    {

        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
                                        'identifier' => 'nested_2',
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
            static::$container->get('unite.cms.manager'), 'domain'
        );
        $d->setAccessible(true);
        $d->setValue(
            static::$container->get('unite.cms.manager'),
            $field->getContentType()->getDomain()
        );
        $domain = $field->getContentType()->getDomain();

        // In this test, we don't care about access checking.
        $admin = new ApiKey();
        $admin->setName('admin_key')->setOrganization($field->getContentType()->getDomain()->getOrganization());
        $domainMember = new DomainMember();
        $domainMember->setDomain($field->getContentType()->getDomain())->setDomainMemberType($field->getContentType()->getDomain()->getDomainMemberTypes()->get('editor'));
        $admin->addDomain($domainMember);
        static::$container->get('security.token_storage')->setToken(
            new PostAuthenticationGuardToken($admin, 'api', [])
        );

        // Create GraphQL Schema
        $schema = static::$container->get('unite.cms.graphql.schema_type_manager')->createSchema($domain, 'Query', 'Mutation');

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
      createCt1(
        persist: true,
        data: {
          f1: [
            {},
            {
              f1: "Foo",
              n1: [
                {
                nested_2: [
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
            nested_2 {
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
            $result->data->createCt1->f1[1]->n1[0]->nested_2[0]->f2
        );
        $this->assertEquals('Foo', $content->getData()['f1'][1]['f1']);


        $field->getSettings()->fields[0]['permissions'] = [
            'list field' => 'true',
            'view field' => 'true',
            'update field' => 'false',
        ];
        $this->em->flush();
        $schema = static::$container->get('unite.cms.graphql.schema_type_manager')->createSchema($domain, 'Query', 'Mutation');

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(
                persist: true,
                data: {
                    f1: [
                        {},
                        {
                            n1: [
                                {
                                    nested_2: [
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
                        nested_2 {
                            f2
                        }
                    }
                }
            }
        }');
        $result = json_decode(json_encode($result->toArray()));
        $this->assertNotEmpty($result->data->createCt1->id);
        $this->assertEmpty($result->data->createCt1->f1[0]->f1);
        $this->assertEmpty($result->data->createCt1->f1[1]->f1);
    }

    public function testWritingGraphQLDataViaMainFirewallWithCSRFProtection()
    {

        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
            static::$container->get('unite.cms.manager'), 'domain'
        );
        $d->setAccessible(true);
        $d->setValue(
            static::$container->get('unite.cms.manager'),
            $field->getContentType()->getDomain()
        );
        $domain = $field->getContentType()->getDomain();

        // In this test, we don't care about access checking.
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
        static::$container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, null, 'main', $admin->getRoles())
        );

        // Create GraphQL Schema
        $schema = static::$container->get('unite.cms.graphql.schema_type_manager')->createSchema($domain, 'Query', 'Mutation');

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
      createCt1(
        persist: true,
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
                'csrf_token' => static::$container->get('security.csrf.token_manager')
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
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), $field->getContentType()->getDomain());
        $o = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'organization');
        $o->setAccessible(true);
        $o->setValue(
            static::$container->get('unite.cms.manager'),
            $field->getContentType()->getDomain()->getOrganization()
        );

        // Validate min rows.
        $context = new ExecutionContext(static::$container->get('validator'), null, static::$container->get('translator'), 'validators');
        $context->setNode([], new Content(), null, '');
        $context->setConstraint(new ValidFieldableContentData());
        $context->setGroup('DEFAULT');

        static::$container->get('unite.cms.field_type_manager')->validateFieldData($field, [], $context);
        $violations = $context->getViolations();

        $this->assertCount(1, $violations);
        $this->assertEquals('['.$field->getIdentifier().']', $violations[0]->getPropertyPath());
        $this->assertEquals(static::$container->get('translator')->trans('collectionfield.too_few_rows', ['%count%' => 1], 'validators'), $violations[0]->getMessage());

        // on DELETE all content is valid.
        $context = new ExecutionContext(static::$container->get('validator'), null, static::$container->get('translator'), 'validators');
        $context->setNode([], new Content(), null, '');
        $context->setConstraint(new ValidFieldableContentData());
        $context->setGroup('DELETE');
        static::$container->get('unite.cms.field_type_manager')->validateFieldData($field, [], $context);
        $this->assertCount(0, $context->getViolations());

        // Validate max rows.
        $context = new ExecutionContext(static::$container->get('validator'), null, static::$container->get('translator'), 'validators');
        $context->setNode([], new Content(), null, '');
        $context->setConstraint(new ValidFieldableContentData());
        $context->setGroup('DEFAULT');
        static::$container->get('unite.cms.field_type_manager')
            ->validateFieldData(
                $field,
                [
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                    ['f1' => 'baa'],
                ],
                $context
            );

        $violations = $context->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('['.$field->getIdentifier().']', $violations[0]->getPropertyPath());
        $this->assertEquals(static::$container->get('translator')->trans('collectionfield.too_many_rows', ['%count%' => 4], 'validators'), $violations[0]->getMessage());

        // on DELETE all content is valid.
        $context = new ExecutionContext(static::$container->get('validator'), null, static::$container->get('translator'), 'validators');
        $context->setNode([], new Content(), null, '');
        $context->setConstraint(new ValidFieldableContentData());
        $context->setGroup('DELETE');
        static::$container->get('unite.cms.field_type_manager')->validateFieldData($field, [], $context);
        $this->assertCount(0, $context->getViolations());

        // Validate additional data (also nested).
        $context = new ExecutionContext(static::$container->get('validator'), null, static::$container->get('translator'), 'validators');
        $context->setNode([], new Content(), null, '');
        $context->setConstraint(new ValidFieldableContentData());
        $context->setGroup('DEFAULT');
        static::$container->get('unite.cms.field_type_manager')->validateFieldData(
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
            ],
            $context
        );
        $violations = $context->getViolations();
        $this->assertCount(4, $violations);
        $this->assertEquals('['.$field->getIdentifier().'][1][foo]',
            $violations[0]->getPropertyPath()
        );
        $this->assertEquals('additional_data', $violations[0]->getMessageTemplate());
        $this->assertEquals('['.$field->getIdentifier().'][2][n1][0][n2][0][f2]', $violations[1]->getPropertyPath());
        $this->assertEquals('invalid_reference_definition', $violations[1]->getMessageTemplate());
        $this->assertEquals('['.$field->getIdentifier().'][3][n1][0][n2][0][f2]', $violations[2]->getPropertyPath());
        $this->assertEquals('required', $violations[2]->getMessageTemplate());
        $this->assertEquals('['.$field->getIdentifier().'][3][n1][0][n2][0][foo]',
            $violations[3]->getPropertyPath()
        );
        $this->assertEquals('additional_data', $violations[3]->getMessageTemplate());

        // on DELETE all content is valid.
        $context = new ExecutionContext(static::$container->get('validator'), null, static::$container->get('translator'), 'validators');
        $context->setNode([], new Content(), null, '');
        $context->setConstraint(new ValidFieldableContentData());
        $context->setGroup('DELETE');
        static::$container->get('unite.cms.field_type_manager')->validateFieldData($field, [], $context);
        $this->assertCount(0, $context->getViolations());
    }

    public function testFormBuilding()
    {

        $field = $this->createContentTypeField('collection');
        $this->user->getDomains()->first()->setDomain($field->getContentType()->getDomain());
        $this->user->getDomains()->first()->getDomain()->setId(1);

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
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);

        $content->setData(
            [
                $field->getIdentifier() => [
                    ['f1' => 'baa'],
                    ['n1' => [['n2' => [['f2' => 'foo',]]]],],
                ],
            ]
        )->setContentType($field->getContentType());
        $form = static::$container->get('unite.cms.fieldable_form_builder')
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


        // Try to submit form data with any keys. Keys should get deleted automatically.
        $form->submit([$field->getIdentifier() => [
            0 => ['f1' => '1', 'n1' => [['n2' => [5 => ['f2' => '1',], 3 => ['f2' => '2',]]]],],
            2 => ['f1' => '2'],
            5 => ['f1' => '3'],
            1 => ['f1' => '4'],
            'foo' => ['f1' => '5'],
        ]]);

        $this->assertEquals([$field->getIdentifier() => [
            ['f1' => '1', 'n1' => [['n2' => [['f2' => '1',], ['f2' => '2',]]]],],
            ['f1' => '2', 'n1' => []],
            ['f1' => '3', 'n1' => []],
            ['f1' => '4', 'n1' => []],
            ['f1' => '5', 'n1' => []],
        ]],$form->getData());
    }
}
