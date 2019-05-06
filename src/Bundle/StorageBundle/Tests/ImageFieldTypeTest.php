<?php

namespace UniteCMS\CollectionFieldBundle\Tests;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\StorageBundle\Model\PreSignedUrl;

class ImageFieldTypeTest extends FieldTypeTestCase
{
    public function testAllowedFieldSettings()
    {
        $field = $this->createContentTypeField('image');
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [],
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
                    'bucket' => [],
                ]
            )
        );

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(2, $errors);
        $this->assertEquals('settings.bucket.endpoint', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('settings.bucket.bucket', $errors->get(1)->getPropertyPath());
        $this->assertEquals('required', $errors->get(1)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [
                        'endpoint' => 'example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                    ],
                ]
            )
        );

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.bucket.endpoint', $errors->get(0)->getPropertyPath());
        $this->assertEquals('storage.absolute_url', $errors->get(0)->getMessageTemplate());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                        "region" => "east",
                        "path" => "/any",
                    ],
                ]
            )
        );

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(0, $errors);

        // Try saving additional data
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                        "region" => "east",
                        "path" => "/any",
                        'foo' => 'baa',
                    ],
                    'file_types' => 'png',
                    'thumbnail_url' => '{endpoint}/{id}/{name}',
                ]
            )
        );

        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.bucket.foo', $errors->get(0)->getPropertyPath());
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
    }

    public function testGettingGraphQLData()
    {

        $field = $this->createContentTypeField('image');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'path' => 'foo-path',
                        'bucket' => 'foo',
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

        // Check file field structure.
        $this->assertArrayHasKey('f1', $type->getFields());
        $this->assertArrayHasKey('name', $type->getField('f1')->getType()->getFields());
        $this->assertArrayHasKey('size', $type->getField('f1')->getType()->getFields());
        $this->assertArrayHasKey('type', $type->getField('f1')->getType()->getFields());
        $this->assertArrayHasKey('id', $type->getField('f1')->getType()->getFields());
        $this->assertArrayHasKey('url', $type->getField('f1')->getType()->getFields());

        $this->assertEquals('String', $type->getField('f1')->getType()->getField('name')->getType()->name);
        $this->assertEquals('Int', $type->getField('f1')->getType()->getField('size')->getType()->name);
        $this->assertEquals('String', $type->getField('f1')->getType()->getField('type')->getType()->name);
        $this->assertEquals('ID', $type->getField('f1')->getType()->getField('id')->getType()->name);
        $this->assertEquals('String', $type->getField('f1')->getType()->getField('url')->getType()->name);
    }

    public function testWritingGraphQLData()
    {

        $field = $this->createContentTypeField('image');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'path' => 'foo-path',
                        'bucket' => 'foo',
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
        $domain = $field->getContentType()->getDomain();

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
            name: "cat.jpg",
            size: 12345,
            type: "image/jpeg",
            id: "XXX-YYY-ZZZ",
            checksum: "XXX"
          }
        }
      ) {
        id,
        f1 {
          name,
          size,
          type,
          id,
          url
        }
       }
    }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));

        // Checksum should be invalid.
        $this->assertEquals(static::$container->get('translator')->trans('storage.invalid_checksum', [], 'validators'), trim($result->errors[0]->message));
        $this->assertEquals(['createCt1', 'data', 'f1'], $result->errors[0]->path);

        // Try with valid checksum.
        $preSignedUrl = new PreSignedUrl('', "XXX-YYY-ZZZ", 'cat.jpg');

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
      createCt1(
        persist: true,
        data: {
          f1: {
            name: "cat.jpg",
            size: 12345,
            type: "image/jpeg",
            id: "XXX-YYY-ZZZ",
            checksum: "'.$preSignedUrl->sign(static::$container->getParameter('kernel.secret')).'"
          }
        }
      ) {
        id,
        f1 {
          name,
          size,
          type,
          id,
          url
        }
       }
    }'
        );
        $result = json_decode(json_encode($result->toArray(true)));

        $this->assertNotEmpty($result->data->createCt1->id);
        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($result->data->createCt1->id);
        $this->assertNotNull($content);
        $this->assertNotNull($result->data->createCt1->f1);
        $this->assertEquals('cat.jpg', $result->data->createCt1->f1->name);
        $this->assertEquals(12345, $result->data->createCt1->f1->size);
        $this->assertEquals('image/jpeg', $result->data->createCt1->f1->type);
        $this->assertEquals('XXX-YYY-ZZZ', $result->data->createCt1->f1->id);
        $this->assertEquals('https://example.com/foo/foo-path/XXX-YYY-ZZZ/cat.jpg', $result->data->createCt1->f1->url);

        // Remove path from field settings should result in an url without path.
        $field->getSettings()->bucket['path'] = null;
        $this->em->flush();

        $result = GraphQL::executeQuery($schema, 'query { getCt1(id: "'.$result->data->createCt1->id.'") {
            f1 {
                url
            }
        }}'
        );
        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals('https://example.com/foo/XXX-YYY-ZZZ/cat.jpg', $result->data->getCt1->f1->url);
    }

    public function testFormBuild()
    {

        $field = $this->createContentTypeField('image');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                        'path' => 'sub-path',
                    ],
                    'thumbnail_url' => 'https://thumbnail.com/{endpoint}/{id}/{name}',
                    'file_types' => 'jpg'
                ]
            )
        );
        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);
        $content->setData(
            [
                'f1' => [
                    'name' => "cat.jpg",
                    'size' => 12345,
                    'type' => "image/jpeg",
                    'id' => "XXX-YYY-ZZZ",
                ],
            ]
        )->setContentType($field->getContentType());
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $formView = $form->createView();

        // Check root file field.
        $root = $formView->getIterator()->current();
        $this->assertEquals('unite-cms-storage-file-field', $root->vars['tag']);

        // Assert values
        $this->assertEquals($content->getData()['f1'], $root->vars['value']);

        // Assert attribute passing
        $this->assertEquals('https://thumbnail.com/{endpoint}/{id}/{name}', $root->vars['attr']['thumbnail-url']);
        $this->assertEquals('jpg', $root->vars['attr']['file-types']);
        $this->assertEquals('https://example.com/foo/sub-path', $root->vars['attr']['endpoint']);
    }
}
