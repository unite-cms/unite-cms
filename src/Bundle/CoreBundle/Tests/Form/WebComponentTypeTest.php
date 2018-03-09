<?php

namespace UnitedCMS\CoreBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use UnitedCMS\CoreBundle\Form\WebComponentType;

class WebComponentTypeTest extends TestCase {

    public function testBuildViewWithEmptyData() {
        $formType = new WebComponentType();
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn(null);

        $formType->buildView($formView, $form, [
            'empty_data' => [
                'foo' => 'baa',
                'foo2' => ['baa'],
            ],
        ]);

        $this->assertEquals('undefined-tag', $formView->vars['tag']);
        $this->assertEquals(json_encode([
            'foo' => 'baa',
            'foo2' => ['baa'],
        ]), $formView->vars['value']);
    }

    public function testDataTransformer() {
        $formType = new WebComponentType();
        $this->assertEquals(json_encode(['foo' => 'baa']), $formType->transform(['foo' => 'baa']));
        $this->assertEquals(null, $formType->reverseTransform(''));
        $this->assertEquals('1', $formType->reverseTransform(1));
        $this->assertEquals('any', $formType->reverseTransform('any'));
    }

}