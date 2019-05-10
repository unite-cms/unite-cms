<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Entity\User;

class CountryFieldTypeTest extends FieldTypeTestCase
{
    public function testCountryFieldTypeWithEmptySettings()
    {
        $ctField = $this->createContentTypeField('country');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testCountryFieldTypeWithInvalidSettings()
    {
        $ctField = $this->createContentTypeField('country');
        
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
                'countries' => ['invalid']
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_country', $errors->get(0)->getMessageTemplate());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'countries' => 'DE',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('not_an_array', $errors->get(0)->getMessageTemplate());

        // test wrong country data
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'countries' => ['XXX'],
            ]
        ));

        // test wrong country
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'countries' => ['de'],
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_country', $errors->get(0)->getMessageTemplate());

        // test wrong country
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'countries' => [23],
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_country', $errors->get(0)->getMessageTemplate());
    }

    public function testCountryFieldTypeWithValidSettings()
    {
        $ctField = $this->createContentTypeField('country');

        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'countries' => ['AT', 'DE', 'US'],
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals([
            'Austria' => 'AT',
            'Germany' => 'DE',
            'United States' => 'US',
        ], $options['choices']);
        $this->assertEquals(null, $options['choice_loader']);
    }

    public function testCountryFieldTypeContentFormBuild()
    {
        $ctField = $this->createContentTypeField('country');
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
                'f1' => 'de',
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

    public function testCountryFieldTypeWritingGraphQLData()
    {
        $ctField = $this->createContentTypeField('country');
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
                        f1: "US"
                    }
                ) 
                {
                    id,
                    f1
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
        $this->assertEquals('US', $result->data->createCt1->f1);

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(
                    persist: true,
                    data: {
                        f1: "wrong"
                    }
                ) 
                {
                    id,
                    f1
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

    public function testCountryFieldTypeFormSubmitData()
    {
        $ctField = $this->createContentTypeField('country');

        $ctField->setSettings(new FieldableFieldSettings(
            [
                'countries' => ['DE', 'US'],
            ]
        ));

        $content = new Content();
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => "DE"
        ]);

        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertEquals("DE", $form->getData()[$ctField->getIdentifier()]);

        # test without countries
        $ctField->setSettings(new FieldableFieldSettings());
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => "US"
        ]);

        $this->assertCount(0, $form->getErrors(true, true));
        $this->assertEquals("US", $form->getData()[$ctField->getIdentifier()]);


        # test with wrong country
        $ctField->setSettings(new FieldableFieldSettings());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => "wrong_country"
        ]);

        $this->assertCount(1, $form->getErrors(true, true));
        $this->assertEquals($ctField->getIdentifier(), $form->getErrors(true, true)->offsetGet(0)->getOrigin()->getName());
        $this->assertEquals('This value is not valid.', $form->getErrors(true, true)->offsetGet(0)->getMessageTemplate());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($ctField->getContentType(), $content, [
            'csrf_protection' => false,
        ]);

        $form->submit([
            $ctField->getIdentifier() => 23
        ]);

        $this->assertCount(1, $form->getErrors(true, true));
        $this->assertEquals($ctField->getIdentifier(), $form->getErrors(true, true)->offsetGet(0)->getOrigin()->getName());
        $this->assertEquals('This value is not valid.', $form->getErrors(true, true)->offsetGet(0)->getMessageTemplate());
    }
}
