<?php

namespace UniteCMS\CollectionFieldBundle\Tests;

use GraphQL\GraphQL;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\StorageBundle\Model\PreSignedUrl;

class FileFieldTypeTest extends FieldTypeTestCase
{
    public function testAllowedFieldSettings()
    {
        $field = $this->createContentTypeField('file');
        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'file_types' => 'txt',
                    'bucket' => [],
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
                    'file_types' => 'txt',
                    'bucket' => [],
                ]
            )
        );

        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(4, $errors);
        $this->assertEquals('settings.bucket.endpoint', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());
        $this->assertEquals('settings.bucket.key', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.required', $errors->get(1)->getMessage());
        $this->assertEquals('settings.bucket.secret', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.required', $errors->get(2)->getMessage());
        $this->assertEquals('settings.bucket.bucket', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.required', $errors->get(3)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'file_types' => 'txt',
                    'bucket' => [
                        'endpoint' => 'example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                    ],
                ]
            )
        );

        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.bucket.endpoint', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.absolute_url', $errors->get(0)->getMessage());

        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'file_types' => 'txt',
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                    ],
                ]
            )
        );

        $errors = $this->container->get('validator')->validate($field);
        $this->assertCount(0, $errors);
    }

    public function testGettingGraphQLData()
    {

        $field = $this->createContentTypeField('file');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'file_types' => '*',
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
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
        $d = new \ReflectionProperty($this->container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue($this->container->get('unite.cms.manager'), $field->getContentType()->getDomain());

        $key = ucfirst($field->getContentType()->getIdentifier()).'Content';
        $type = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
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

        $field = $this->createContentTypeField('file');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'file_types' => '*',
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
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
        $d = new \ReflectionProperty($this->container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue($this->container->get('unite.cms.manager'), $field->getContentType()->getDomain());
        $domain = $field->getContentType()->getDomain();

        // In this test, we don't care about access checking.
        $admin = new User();
        $admin->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, null, 'api', $admin->getRoles())
        );

        // Create GraphQL Schema
        $schemaTypeManager = $this->container->get('unite.cms.graphql.schema_type_manager');

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
        $result = json_decode(json_encode($result->toArray(true)));

        // Checksum should be invalid.
        $this->assertEquals('ERROR: validation.invalid_checksum', trim($result->errors[0]->message));

        // Try with valid checksum.
        $preSignedUrl = new PreSignedUrl('', "XXX-YYY-ZZZ", 'cat.jpg');

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
      createCt1(
        data: {
          f1: {
            name: "cat.jpg",
            size: 12345,
            type: "image/jpeg",
            id: "XXX-YYY-ZZZ",
            checksum: "'.$preSignedUrl->sign($this->container->getParameter('kernel.secret')).'"
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
        $this->assertEquals('https://example.com/foo/XXX-YYY-ZZZ/cat.jpg', $result->data->createCt1->f1->url);
    }

    public function testFormBuild()
    {

        $field = $this->createContentTypeField('file');
        $field->setIdentifier('f1');
        $field->getContentType()->setIdentifier('ct1');
        $field->setSettings(
            new FieldableFieldSettings(
                [
                    'file_types' => '*',
                    'bucket' => [
                        'endpoint' => 'https://example.com',
                        'key' => 'XXX',
                        'secret' => 'XXX',
                        'bucket' => 'foo',
                    ],
                ]
            )
        );
        $content = new Content();
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
        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm(
            $field->getContentType(),
            $content
        );
        $formView = $form->createView();

        // Check root file field.
        $root = $formView->getIterator()->current();
        $this->assertEquals('unite-cms-storage-file-field', $root->vars['tag']);

        // Assert values
        $this->assertEquals(json_encode($content->getData()['f1']), $root->vars['value']);
    }
}
