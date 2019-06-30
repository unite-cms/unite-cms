<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 08.11.18
 * Time: 11:00
 */

namespace UniteCMS\CoreBundle\Tests\Form\Extension;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Form\Extension\UniteCMSCoreTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UniteCMSCoreTypeExtensionTest extends TypeTestCase
{
    protected function getTypeExtensions()
    {
        return array(
            new UniteCMSCoreTypeExtension($this->createMock(TranslatorInterface::class))
        );
    }

    public function testUniteCMSCoreTypeExtension()
    {
        $form = $this->factory->create(FormType::class, null, ['not_empty' => true]);
        $this->assertTrue($form->getConfig()->getOption('not_empty'));

        $form = $this->factory->create(IntegerType::class, null, ['not_empty' => true]);
        $this->assertTrue($form->getConfig()->getOption('not_empty'));

        $form = $this->factory->create(FileType::class, null, ['not_empty' => true]);
        $this->assertTrue($form->getConfig()->getOption('not_empty'));

        $form = $this->factory->create(EmailType::class, null, ['description' => 'bla']);
        $this->assertEquals('bla', $form->getConfig()->getOption('description'));

        $form = $this->factory->create(RangeType::class, null, ['description' => 'bla1']);
        $this->assertEquals('bla1', $form->getConfig()->getOption('description'));

        $form = $this->factory->create(TextType::class, null, ['description' => 'bla2']);
        $this->assertEquals('bla2', $form->getConfig()->getOption('description'));

        $form = $this->factory->create(TextType::class, null, ['form_group' => 'foo']);
        $this->assertEquals('foo', $form->getConfig()->getOption('form_group'));

        $form = $this->factory->create(TextType::class, null, ['form_group' => 'foo']);
        $this->assertEquals('foo', $form->getConfig()->getOption('form_group'));

        $form = $this->factory->create(TextType::class, null, ['form_group' => true]);
        $this->assertEquals(true, $form->getConfig()->getOption('form_group'));

        $form = $this->factory->create(TextType::class, null, ['form_group' => false]);
        $this->assertEquals(false, $form->getConfig()->getOption('form_group'));

        $form = $this->factory->create(TextType::class, null, ['form_group' => null]);
        $this->assertEquals(null, $form->getConfig()->getOption('form_group'));

        // test if description and from_group is in form vars
        $form = $this->createMock(Form::class);
        $formExtension = new UniteCMSCoreTypeExtension($this->createMock(TranslatorInterface::class));
        $formView = new FormView();
        $formExtension->buildView(
            $formView,
            $form,
            [
                'description' => 'test123',
                'form_group' => 'foo',
            ]
        );

        $this->assertEquals('test123', $formView->vars['description']);
        $this->assertEquals('foo', $formView->vars['form_group']);

        // Test different false options (to hide the field)
        $formExtension->buildView($formView, $form, ['form_group' => 'off']);
        $this->assertEquals(false, $formView->vars['form_group']);

        $formExtension->buildView($formView, $form, ['form_group' => false]);
        $this->assertEquals(false, $formView->vars['form_group']);

        $formExtension->buildView($formView, $form, ['form_group' => 'no']);
        $this->assertEquals(false, $formView->vars['form_group']);

        // Test true
        $formExtension->buildView($formView, $form, ['form_group' => true]);
        $this->assertArrayNotHasKey('form_group', $formView->vars);

        // Test null
        $formExtension->buildView($formView, $form, ['form_group' => null]);
        $this->assertArrayNotHasKey('form_group', $formView->vars);
    }

}