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
            new UniteCMSCoreTypeExtension()
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

        // test if description is in form vars
        $form = $this->createMock(Form::class);
        $formExtension = new UniteCMSCoreTypeExtension();
        $formView = new FormView();
        $formExtension->buildView(
            $formView,
            $form,
            [
                'description' => 'test123'
            ]
        );

        $this->assertEquals('test123', $formView->vars['description']);
    }

}