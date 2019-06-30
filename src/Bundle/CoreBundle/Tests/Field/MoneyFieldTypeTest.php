<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Entity\User;


class MoneyFieldTypeTest extends FieldTypeTestCase
{
    public function testMoneyFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('money');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testMoneyFieldTypeWithInvalidSettings()
    {
        $ctField = $this->createContentTypeField('money');
        
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'foo' => 'baa',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => ['invalid']
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_currency', $errors->get(0)->getMessageTemplate());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => 'eur',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('not_an_array', $errors->get(0)->getMessageTemplate());

        // test wrong currency data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => ['XXX'],
            ]
        ));

        // test wrong currency
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => ['eur'],
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_currency', $errors->get(0)->getMessageTemplate());


        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_currency', $errors->get(0)->getMessageTemplate());

        // test wrong currency
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => [23],
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_currency', $errors->get(0)->getMessageTemplate());
    }

    public function testMoneyFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('money');

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => ['EUR', 'USD'],
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals(['EUR', 'USD'], $options['currencies']);
    }

    public function testMoneyFieldTypeGettingGraphQLData()
    {

        $ctField = $this->createContentTypeField('money');
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
            'MoneyField',
            $ctField->getContentType()->getDomain()
        );

        $this->assertInstanceOf(ObjectType::class, $type);

        // Check Link field structure.
        $this->assertArrayHasKey('value', $type->getFields());
        $this->assertArrayHasKey('currency', $type->getFields());

        $this->assertEquals('Float', $type->getField('value')->getType()->name);
        $this->assertEquals('String', $type->getField('currency')->getType()->name);
    }

    public function testMoneyFieldTypeContentFormBuild()
    {
        $ctField = $this->createContentTypeField('money');
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
                    'value' => 12345.67,
                    'currency' => 'EUR',
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

    public function testMoneyFieldTypeWritingGraphQLData()
    {
        $ctField = $this->createContentTypeField('money');
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
                            value: 123.456,
                            currency: "EUR"
                        }
                    }
                ) 
                {
                    id,
                    f1 {
                        value,
                        currency
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

        // value gets rounded!
        $this->assertEquals(123.46, $result->data->createCt1->f1->value);
        $this->assertEquals('EUR', $result->data->createCt1->f1->currency);

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(
                    persist: true,
                    data: {
                        f1: {
                            value: 123,
                            currency: "wrong_currency"
                        }
                    }
                ) 
                {
                    id,
                    f1 {
                        value,
                        currency
                    }
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));

        # check if error
        $this->assertCount(1, $result->errors);
        $this->assertEquals('This value is not valid.', $result->errors[0]->message);
    }

    public function testMoneyFieldTypeFormSubmitData()
    {
        $ctField = $this->createContentTypeField('money');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'currencies' => ['EUR', 'USD'],
            ]
        ));

        $content = new Content();
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'value' => 12345.6789,
            'currency' => 'EUR',
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertEquals([
            'value' => 12345.68,
            'currency' => 'EUR',
        ], $form->getData()[$ctField->getIdentifier()]);

        # test without currency
        $ctField->setSettings(new FieldableFieldSettings());
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'value' => 12345.6789,
            'currency' => 'USD',
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertEquals([
            'value' => 12345.68,
            'currency' => 'USD',
        ], $form->getData()[$ctField->getIdentifier()]);


        # test with wrong currency
        $ctField->setSettings(new FieldableFieldSettings());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'value' => 12345.6789,
            'currency' => 'fooooo',
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertCount(1, $form->getErrors(true, true));
        $this->assertEquals('currency', $form->getErrors(true, true)->offsetGet(0)->getOrigin()->getName());
        $this->assertEquals('This value is not valid.', $form->getErrors(true, true)->offsetGet(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $submit_data = [
            'value' => '122,23.23',
            'currency' => 'EUR',
        ];

        $form->submit([
            $ctField->getIdentifier() => $submit_data
        ]);

        $this->assertCount(1, $form->getErrors(true, true));
        $this->assertEquals('value', $form->getErrors(true, true)->offsetGet(0)->getOrigin()->getName());
        $this->assertEquals('This value is not valid.', $form->getErrors(true, true)->offsetGet(0)->getMessageTemplate());
    }
}
