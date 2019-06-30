<?php

namespace UniteCMS\CoreBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use UniteCMS\CoreBundle\Form\WebComponentType;

class WebComponentTypeTest extends TestCase
{

    public function testBuildViewWithEmptyData()
    {
        $formType = new WebComponentType();
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn(null);

        $formType->buildView(
            $formView,
            $form,
            [
                'empty_data' => [
                    'foo' => 'baa',
                    'foo2' => ['baa'],
                ],
            ]
        );

        $this->assertEquals('undefined-tag', $formView->vars['tag']);
        $this->assertEquals([
                'foo' => 'baa',
                'foo2' => ['baa'],
            ],
            $formView->vars['value']
        );
    }
}
