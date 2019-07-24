<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class TokenFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings()
    {
        // Content Type Field with empty settings should be valid.
        $ctField = $this->createContentTypeField('token');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testContentTypeFieldTypeWithInvalidSettings()
    {
        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('token');
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'foo' => 'baa',
                'description' => $this->generateRandomMachineName(500)
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('too_long', $errors->get(1)->getMessageTemplate());
    }

    public function testContentFormBuild() {

        $ctField = $this->createContentTypeField('token');

        $ctField->setIdentifier('f1');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('ct1');
        $content = new Content();
        $content->setContentType($ctField->getContentType());

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm(
            $ctField->getContentType(),
            $content
        );

        $formView = $form->createView();
        $root = $formView->getIterator()->current();

        $this->assertEquals(true, $root->vars['attr']['readonly']);
        $this->assertEquals('Will be generated on create.', $root->vars['attr']['placeholder']);
    }

    public function testGenerateId() {

        $ctField = $this->createContentTypeField('token');
        $ctField->setIdentifier('f1');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('baa');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('ct1');
        $content = new Content();
        $content->setContentType($ctField->getContentType());

        $ctField2 = new ContentTypeField();
        $ctField2->setType('text')->setContentType($ctField->getContentType())->setIdentifier('f2')->setTitle('F2');

        $this->em->persist($content->getContentType()->getDomain()->getOrganization());
        $this->em->persist($content->getContentType()->getDomain());
        $this->em->persist($content->getContentType());

        $content->setData([
            'f2' => 'Foo',
        ]);
        $this->em->persist($content);
        $this->em->flush($content);
        $this->em->refresh($content);

        $this->assertEquals('Foo', $content->getData()['f2']);
        $this->assertNotEmpty($content->getData()['f1']);
        $token = $content->getData()['f1'];

        $content->setData(['f1' => 'XXX', 'f2' => 'Updated']);
        $this->em->flush($content);
        $this->em->refresh($content);
        $this->assertEquals([
            'f1' => $token,
            'f2' => 'Updated',
        ], $content->getData());

        $content->setData(['f2' => 'Updated1']);
        $this->em->flush($content);
        $this->em->refresh($content);
        $this->assertEquals([
            'f1' => $token,
            'f2' => 'Updated1',
        ], $content->getData());
    }
}
