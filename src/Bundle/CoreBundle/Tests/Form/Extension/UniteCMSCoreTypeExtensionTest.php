<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 08.11.18
 * Time: 11:00
 */

namespace UniteCMS\CoreBundle\Tests\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\FormView;
use UniteCMS\CoreBundle\Form\Extension\UniteCMSCoreTypeExtension;
use Symfony\Component\Form\FormExtensionInterface;


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

        $form = $this->factory->create(TextType::class, null, ['description' => 'hallo']);
        $this->assertEquals('hallo', $form->getConfig()->getOption('description'));

        /*$this->assertEquals('undefined-tag', $formView->vars['tag']);
        $this->assertEquals(
            json_encode(
                [
                    'foo' => 'baa',
                    'foo2' => ['baa'],
                ]
            ),
            $formView->vars['value']
        );*/
    }

}