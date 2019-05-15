<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Entity\User;


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

        // test wrong intial data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'default' => 123,
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_initial_data', $errors->get(0)->getMessageTemplate());
    }

    public function testLinkFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('link');

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => false,
                'description' => 'my description',
                'not_empty' => false,
                'default' => ['url' => 'https://www.unitecms.io'],
                'form_group' => 'foo',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals(true, $options['title_widget']);
        $this->assertEquals(false, $options['target_widget']);

    }

    public function testLinkFieldTypeGettingGraphQLData()
    {

        $ctField = $this->createContentTypeField('link');
        $ctField->setIdentifier('f1');
        $ctField->getContentType()->setIdentifier('ct1');
        
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => true,
            ]
        ));

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
            'LinkField',
            $ctField->getContentType()->getDomain()
        );

        $this->assertInstanceOf(ObjectType::class, $type);

        // Check Link field structure.
        $this->assertArrayHasKey('url', $type->getFields());
        $this->assertArrayHasKey('target', $type->getFields());
        $this->assertArrayHasKey('title',$type->getFields());

        $this->assertEquals('String', $type->getField('url')->getType()->name);
        $this->assertEquals('String', $type->getField('target')->getType()->name);
        $this->assertEquals('String', $type->getField('title')->getType()->name);
    }

    public function testLinkFieldTypeContentFormBuild()
    {
        $ctField = $this->createContentTypeField('link');
        $ctField->setIdentifier('f1');
        
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa_baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo_foo');
        $ctField->getContentType()->setIdentifier('ct1_ct1');
        
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => true,
            ]
        ));

        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);
        $content->setData(
            [
                'f1' => [
                    'url' => "https://www.orf.at",
                    'target' => "_blank",
                    'title' => "AHAHAH"
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

        $content->setData(
            [
                'f1' => [
                    'url' => "https://www.orf.at",
                    'title' => "AHAHAH"
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

    public function testLinkFieldTypeWritingGraphQLData()
    {
        $ctField = $this->createContentTypeField('link');
        $ctField->setIdentifier('f1');
        $ctField->getContentType()->setIdentifier('ct1');
       
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => true,
            ]
        ));

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
                            url: "https://www.orf.at",
                        }
                    }
                ) 
                {
                    id,
                    f1 {
                        url,
                        title,
                        target
                    }
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));

        # check if content
        $this->assertNotEmpty($result->data->createCt1->id);

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($result->data->createCt1->id);

        $this->assertNotNull($content);
        $this->assertNotNull($result->data->createCt1->f1);
        $this->assertEquals('https://www.orf.at', $result->data->createCt1->f1->url);
        $this->assertNull($result->data->createCt1->f1->title);
        $this->assertNull($result->data->createCt1->f1->target);

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(
                    persist: true,
                    data: {
                        f1: {
                            url: "https://www.orf.at",
                            target: "_blank",
                            title: "AHAHAH"
                        }
                    }
                ) 
                {
                    id,
                    f1 {
                        url,
                        target,
                        title
                    }
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));

        # check if content
        $this->assertNotEmpty($result->data->createCt1->id);

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($result->data->createCt1->id);
        
        $this->assertNotNull($content);
        $this->assertNotNull($result->data->createCt1->f1);
        $this->assertEquals('https://www.orf.at', $result->data->createCt1->f1->url);
        $this->assertEquals('_blank', $result->data->createCt1->f1->target);
        $this->assertEquals('AHAHAH', $result->data->createCt1->f1->title);

        $content_id = $result->data->createCt1->id;

        // Test getting non-empty data via api.
        $empty_content = new Content();
        $empty_content->setContentType($ctField->getContentType());
        $this->em->persist($empty_content);
        $this->em->flush();

        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                getCt1(id: "'.$content_id.'") {
                    f1 {
                        url,
                        target,
                        title
                    }
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertNotNull($content);
        $this->assertNotNull($result->data->getCt1->f1);
        $this->assertEquals('https://www.orf.at', $result->data->getCt1->f1->url);
        $this->assertEquals('_blank', $result->data->getCt1->f1->target);
        $this->assertEquals('AHAHAH', $result->data->getCt1->f1->title);

        // test return only some
        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                getCt1(id: "'.$content_id.'") {
                    f1 {
                        url
                    }
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertTrue(empty($result->errors));
        $this->assertEquals('https://www.orf.at', $result->data->getCt1->f1->url);

        // Test getting empty data via api.
        $empty_content = new Content();
        $empty_content->setContentType($ctField->getContentType());
        
        $this->em->persist($empty_content);
        $this->em->flush();

        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                getCt1(id: "'.$empty_content->getId().'") {
                    f1 {
                        url,
                        target,
                        title
                    }
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertTrue(empty($result->errors));
        $this->assertEmpty($result->data->getCt1->f1->url);
        $this->assertEmpty($result->data->getCt1->f1->target);
        $this->assertEmpty($result->data->getCt1->f1->title);
    }

    public function testLinkFieldTypeFormSubmitData()
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

        $this->assertEquals($submit_data, $form->getData()[$ctField->getIdentifier()]);

        # test without target widget
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'title_widget' => true,
                'target_widget' => false,
            ]
        ));
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'url' => 'http://www.orf.at',
            'title' => 'Serwas'
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertEquals($submit_data, $form->getData()[$ctField->getIdentifier()]);
    }
}