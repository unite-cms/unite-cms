<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\SchemaType\Types\LocationFieldAdminLevelType;

class LocationFieldTypeTest extends FieldTypeTestCase
{
    public function testLocationFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('location');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testLocationFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('location');

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'description' => 'Foo',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals('Foo', $options['description']);
    }

    public function testLocationFieldTypeGettingGraphQLData()
    {

        $ctField = $this->createContentTypeField('location');
        $ctField->setIdentifier('f1');
        $ctField->getContentType()->setIdentifier('ct1');
        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->flush();

        $this->em->refresh($ctField->getContentType()->getDomain());
        $this->em->refresh($ctField->getContentType());
        $this->em->refresh($ctField);

        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain());
        
        $type = static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            'LocationField',
            $ctField->getContentType()->getDomain()
        );

        $this->assertInstanceOf(ObjectType::class, $type);


        $this->assertArrayHasKey('provided_by', $type->getFields());
        $this->assertArrayHasKey('id', $type->getFields());
        $this->assertArrayHasKey('category', $type->getFields());
        $this->assertArrayHasKey('display_name', $type->getFields());
        $this->assertArrayHasKey('latitude', $type->getFields());
        $this->assertArrayHasKey('longitude', $type->getFields());
        $this->assertArrayHasKey('bound_south', $type->getFields());
        $this->assertArrayHasKey('bound_west', $type->getFields());
        $this->assertArrayHasKey('bound_north', $type->getFields());
        $this->assertArrayHasKey('bound_east', $type->getFields());
        $this->assertArrayHasKey('street_number', $type->getFields());
        $this->assertArrayHasKey('street_name', $type->getFields());
        $this->assertArrayHasKey('postal_code', $type->getFields());
        $this->assertArrayHasKey('locality', $type->getFields());
        $this->assertArrayHasKey('sub_locality', $type->getFields());
        $this->assertArrayHasKey('admin_levels', $type->getFields());
        $this->assertArrayHasKey('country_code', $type->getFields());

        $this->assertEquals('String', $type->getField('provided_by')->getType()->name);
        $this->assertEquals('ID', $type->getField('id')->getType()->name);
        $this->assertEquals('String', $type->getField('category')->getType()->name);
        $this->assertEquals('String', $type->getField('display_name')->getType()->name);
        $this->assertEquals('Float', $type->getField('latitude')->getType()->name);
        $this->assertEquals('Float', $type->getField('longitude')->getType()->name);
        $this->assertEquals('Float', $type->getField('bound_south')->getType()->name);
        $this->assertEquals('Float', $type->getField('bound_west')->getType()->name);
        $this->assertEquals('Float', $type->getField('bound_north')->getType()->name);
        $this->assertEquals('Float', $type->getField('bound_east')->getType()->name);
        $this->assertEquals('String', $type->getField('street_number')->getType()->name);
        $this->assertEquals('String', $type->getField('street_name')->getType()->name);
        $this->assertEquals('String', $type->getField('postal_code')->getType()->name);
        $this->assertEquals('String', $type->getField('locality')->getType()->name);
        $this->assertEquals('String', $type->getField('sub_locality')->getType()->name);

        $this->assertInstanceOf(ListOfType::class, $type->getField('admin_levels')->config['type']);
        $this->assertInstanceOf(LocationFieldAdminLevelType::class, $type->getField('admin_levels')->config['type']->getWrappedType());
        $this->assertEquals('String', $type->getField('country_code')->getType()->name);
    }

    public function testLocationFieldTypeContentFormBuild()
    {
        $ctField = $this->createContentTypeField('location');
        $ctField->setIdentifier('f1');
        
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa_baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo_foo');
        $ctField->getContentType()->setIdentifier('ct1_ct1');

        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);
        $content->setData(
            [
                'f1' => [
                    'providedBy' => 'nominatim',
                    'id' => '12345',
                    'category' => 'place',
                    'displayName' => 'Foo in street 1-2',
                    'latitude' => -48.3425176,
                    'longitude' => -16.4855965,
                    'bound_south' => 48.3425176,
                    'bound_west' => 16.4855965,
                    'bound_north' => 48.3425176,
                    'bound_east' => 16.4855965,
                    'streetNumber' => '1-2',
                    'streetName' => 'Foo Street',
                    'postalCode' => '1010',
                    'locality' => 'Foo',
                    'subLocality' => 'Alsergrund',
                    'adminLevels' => [
                        [
                            'name' => 'Wien',
                            'code' => 'code',
                            'level' => 1,
                        ]
                    ],
                    'countryCode' => 'AT',
                ],
            ]
        )->setContentType($ctField->getContentType());
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );

        $formView = $form->createView();
        $root = $formView->getIterator()->current();
        $this->assertEquals($content->getData()['f1'], $root->vars['value']);
    }

    public function testLocationFieldTypeWritingGraphQLData()
    {
        $ctField = $this->createContentTypeField('location');
        $ctField->setIdentifier('f1');
        $ctField->getContentType()->setIdentifier('ct1');
        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->flush();

        $this->em->refresh($ctField->getContentType()->getDomain());
        $this->em->refresh($ctField->getContentType());
        $this->em->refresh($ctField);

        // Inject created domain into untied.cms.manager.
        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'),$ctField->getContentType()->getDomain());
        $domain = $ctField->getContentType()->getDomain();

        // In this test, we don't care about access checking.
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
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
                        f1: {
                            provided_by: "nominatim",
                            id: "12345",
                            category: "place",
                            display_name: "Foo in street 1-2",
                            latitude: -48.3425176,
                            longitude: -16.4855965,
                            bound_south: 48.3425176,
                            bound_west: 16.4855965,
                            bound_north: 48.3425176,
                            bound_east: 16.4855965,
                            street_number: "1-2",
                            street_name: "Foo Street",
                            postal_code: "1010",
                            locality: "Foo",
                            sub_locality: "Alsergrund",
                            admin_levels: [
                                {
                                    name: "Wien",
                                    code: "code",
                                    level: 1,
                                }
                            ]
                            country_code: "AT",
                        }
                    }
                ) 
                {
                    id,
                    f1 {
                        provided_by,
                        id,
                        category,
                        display_name,
                        latitude,
                        longitude,
                        bound_south,
                        bound_west,
                        bound_north,
                        bound_east,
                        street_number,
                        street_name,
                        postal_code,
                        locality,
                        sub_locality,
                        admin_levels {
                            name,
                            code,
                            level,
                        }
                        country_code,
                    }
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));

        # check if content;
        $this->assertNotEmpty($result->data->createCt1->id);

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($result->data->createCt1->id);

        $this->assertNotNull($content);
        $this->assertNotNull($result->data->createCt1->f1);
        $this->assertEquals([
            'provided_by' => 'nominatim',
            'id' => '12345',
            'category' => 'place',
            'display_name' => 'Foo in street 1-2',
            'latitude' => -48.3425176,
            'longitude' => -16.4855965,
            'admin_levels' => [
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ],
            ],
            'country_code' => 'AT',
            'bound_south' => 48.3425176,
            'bound_west' => 16.4855965,
            'bound_north' => 48.3425176,
            'bound_east' => 16.4855965,
            'street_number' => "1-2",
            'street_name' => "Foo Street",
            'postal_code' => "1010",
            'locality' => "Foo",
            'sub_locality' => 'Alsergrund',
        ], $content->getData()['f1']);
    }

    public function testLocationFieldTypeFormSubmitData()
    {
        $ctField = $this->createContentTypeField('location');
        $content = new Content();
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'provided_by' => 'nominatim',
            'id' => '12345',
            'category' => 'place',
            'display_name' => 'Foo in street 1-2',
            'latitude' => '-48.3425176',
            'longitude' => 34,
            'admin_levels' => [
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ],
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ],
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ]
            ],
            'country_code' => 'AT',
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertEquals([
            'provided_by' => 'nominatim',
            'id' => '12345',
            'category' => 'place',
            'display_name' => 'Foo in street 1-2',
            'latitude' => '-48.3425176',
            'longitude' => 34,
            'admin_levels' => [
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ],
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ],
                [
                    'name' => 'Wien',
                    'code' => 'code',
                    'level' => 1,
                ]
            ],
            'country_code' => 'AT',
            'bound_south' => null,
            'bound_west' => null,
            'bound_north' => null,
            'bound_east' => null,
            'street_number' => null,
            'street_name' => null,
            'postal_code' => null,
            'locality' => null,
            'sub_locality' => null,
        ], $form->getData()[$ctField->getIdentifier()]);
    }
}
