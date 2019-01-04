<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\User;


class AutoTextFieldTypeTest extends FieldTypeTestCase
{
    public function testEmptySettings()
    {
        $ctField = $this->createContentTypeField('auto_text');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.expression', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
    }

    public function testWithInvalidSettings()
    {
        $ctField = $this->createContentTypeField('auto_text');

        // Invalid expression
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'expression' => 'WE(DO(NOT(UNSERSTAND(THIS)',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.expression', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_expression', $errors->get(0)->getMessageTemplate());

        // Valid expression, invalid auto_update
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'expression' => 'content.id',
                'auto_update' => 'foo',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.auto_update', $errors->get(0)->getPropertyPath());
        $this->assertEquals('noboolean_value', $errors->get(0)->getMessageTemplate());

        // Valid expression, valid auto_update, invalid text_widget
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'expression' => 'content.id',
                'auto_update' => true,
                'text_widget' => 'radio',
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.text_widget', $errors->get(0)->getPropertyPath());
        $this->assertEquals('invalid_auto_text_widget', $errors->get(0)->getMessageTemplate());

        // All settings valid
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'expression' => '"Any"',
                'auto_update' => false,
                'text_widget' => 'textarea',
            ]
        ));

        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testGettingGraphQLData()
    {

        $ctField = $this->createContentTypeField('auto_text');
        $ctField->setIdentifier('f1');
        $ctField->getContentType()->setIdentifier('ct1');
        
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'expression' => '"Any", content.id',
                'auto_update' => true,
            ]
        ));

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->flush();

        $this->em->refresh($ctField->getContentType()->getDomain());
        $this->em->refresh($ctField->getContentType());
        $this->em->refresh($ctField);

        $type = static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            'AutoTextField',
            $ctField->getContentType()->getDomain()
        );

        $this->assertInstanceOf(ObjectType::class, $type);

        // Check Link field structure.
        $this->assertArrayHasKey('auto', $type->getFields());
        $this->assertArrayHasKey('text', $type->getFields());

        $this->assertEquals('Boolean', $type->getField('auto')->getType()->name);
        $this->assertEquals('String', $type->getField('text')->getType()->name);
    }

    public function testContentFormBuild()
    {
        $ctField = $this->createContentTypeField('auto_text');
        $ctField->setIdentifier('f1');
        
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa_baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo_foo');
        $ctField->getContentType()->setIdentifier('ct1_ct1');

        $ctTextField = $this->createContentTypeField('text');
        $ctTextField->setIdentifier('title');
        $ctField->getContentType()->addField($ctTextField);
        
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'expression' => '"Any, " ~ content.data.title',
                'auto_update' => true,
            ]
        ));

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->flush();

        $this->em->refresh($ctField->getContentType()->getDomain());
        $this->em->refresh($ctField->getContentType());
        $this->em->refresh($ctField);

        $content = new Content();
        $id = new \ReflectionProperty($content, 'id');
        $id->setAccessible(true);
        $id->setValue($content, 1);
        $content->setData(
            [
                'f1' => [
                    'auto' => true,
                    'text' => '',
                ],
            ]
        )->setContentType($ctField->getContentType());
        
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );

        $formView = $form->createView();
        $root = $formView->getIterator()->current();
        $this->assertEquals([
            'auto' => true,
            'text' => ''
        ], $root->vars['value']);

        $content->setData(
            [
                'f1' => [
                    'auto' => false,
                    'text' => 'foo'
                ],
            ]
        )->setContentType($ctField->getContentType());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );

        $formView = $form->createView();
        $root = $formView->getIterator()->current();
        $this->assertEquals([
            'auto' => false,
            'text' => 'foo'
        ], $root->vars['value']);


        $content->setData([
            'title' => '1',
        ]);
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );
        $form->submit(['f1' => ['auto' => 'on', 'text' => 'will_get_ov by auto']], false);
        $content->setData($form->getData());
        $this->em->persist($content);
        $this->em->flush($content);

        $this->assertEquals(['f1' => [
            'auto' => true,
            'text' => 'Any, 1'
        ], 'title' => '1'], $content->getData());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );
        $form->submit(['f1' => ['text' => 'foo'], 'title' => 1]);
        $content->setData($form->getData());
        $this->em->flush($content);

        $this->assertEquals(['f1' => [
            'auto' => false,
            'text' => 'foo'
        ], 'title' => '1'], $content->getData());

        // Try to update text, but auto_update was set to false: Should update, because previous auto was false
        $ctField->getSettings()->auto_update = false;
        $content->setData(['f1' => [
            'text' => 'foo',
            'auto' => false,
        ], 'title' => '1']);
        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );
        $form->submit(['f1' => ['auto' => true], 'title' => '1']);
        $content->setData($form->getData());
        $this->em->flush($content);

        $this->assertEquals(['f1' => [
            'auto' => true,
            'text' => 'Any, 1'
        ], 'title' => '1'], $content->getData());

        // Try to update text, but auto_update=false: Should not work, because prev. auto was also true.
        $content->setData(['f1' => [
            'auto' => true,
            'text' => 'Any, 1'
        ], 'title' => '2']);

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );
        $form->submit(['f1' => ['auto' => 'on']], false);
        $content->setData($form->getData());
        $this->em->flush($content);

        $this->assertEquals(['f1' => [
            'auto' => true,
            'text' => 'Any, 1'
        ], 'title' => '2'], $content->getData());


        // Try to use currently submitted data.
        $ctField->getSettings()->auto_update = true;
        $form1 = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );
        $form1->submit([
            'f1' => ['auto' => 'on', 'text' => 'this is not relevant'],
            'title' => 'My new title'
        ]);
        $content->setData($form1->getData());
        $this->em->flush($content);

        $this->assertEquals(['f1' => [
            'auto' => true,
            'text' => 'Any, My new title'
        ], 'title' => 'My new title'], $content->getData());

    }

    public function testWritingGraphQLData()
    {
        $field = $this->createContentTypeField('auto_text');
        $field->setIdentifier('f1');

        $field->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $field->getContentType()->getDomain()->setIdentifier('foo');
        $field->getContentType()->setIdentifier('ct1');

        $ctTextField = $this->createContentTypeField('text');
        $ctTextField->setIdentifier('title');
        $field->getContentType()->addField($ctTextField);

        $field->setSettings(new FieldableFieldSettings(
            [
                'expression' => '"Any, " ~ content.data.title',
                'auto_update' => false,
            ]
        ));

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
                createCt1(persist: false, data: { title: "My title", f1: { auto: true } }) {
                    f1 { text, auto, text_generated },
                    title
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)), true);
        $this->assertEquals([
            'f1' => ['text' => '', 'auto' => true, 'text_generated' => 'Any, My title'],
            'title' => 'My title'
        ], $result['data']['createCt1']);

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(persist: false, data: { title: "My title", f1: { auto: true, text: "Override" } }) {
                    f1 { text, auto, text_generated },
                    title
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)), true);
        $this->assertEquals([
            'f1' => ['text' => 'Override', 'auto' => true, 'text_generated' => 'Any, My title'],
            'title' => 'My title'
        ], $result['data']['createCt1']);

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(persist: false, data: { title: "My title", f1: { auto: false, text: "Override" } }) {
                    f1 { text, auto, text_generated },
                    title
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)), true);
        $this->assertEquals([
            'f1' => ['text' => 'Override', 'auto' => false, 'text_generated' => 'Any, My title'],
            'title' => 'My title'
        ], $result['data']['createCt1']);


        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                createCt1(persist: true, data: { title: "My title", f1: { auto: true, text: "" } }) {
                    id,
                    f1 { text, auto, text_generated },
                    title
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)), true);
        $this->assertEquals([
            'id' => $result['data']['createCt1']['id'],
            'f1' => ['text' => 'Any, My title', 'auto' => true, 'text_generated' => 'Any, My title'],
            'title' => 'My title'
        ], $result['data']['createCt1']);

        $result = GraphQL::executeQuery(
            $schema,
            'mutation { 
                updateCt1(id: "'.$result['data']['createCt1']['id'].'"persist: true, data: { title: "Updated", f1: { auto: true, text: "" } }) {
                    f1 { text, auto, text_generated },
                    title
                }
            }'
        );

        $result = json_decode(json_encode($result->toArray(true)), true);
        $this->assertEquals([
            'f1' => ['text' => 'Any, My title', 'auto' => true, 'text_generated' => 'Any, Updated'],
            'title' => 'Updated'
        ], $result['data']['updateCt1']);
    }
}
