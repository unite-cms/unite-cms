<?php

namespace UniteCMS\CoreBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormView;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\ReferenceOfType;
use UniteCMS\CoreBundle\View\Types\TableViewType;
use UniteCMS\CoreBundle\View\ViewParameterBag;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\View\Types\Factories\TableViewConfigurationFactory;

class ReferenceOfTypeTest extends TestCase
{

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Required "view" form option must be of type UniteCMS\CoreBundle\Entity\View.
     */
    public function testBuildFormWithoutView()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $formType->buildView($formView, $form, []);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Required "view" form option must be of type UniteCMS\CoreBundle\Entity\View.
     */
    public function testBuildFormWithInvalidView()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $formType->buildView($formView, $form, ['view' => 'foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Required "reference_field" form option must be of type UniteCMS\CoreBundle\Entity\ContentTypeField.
     */
    public function testBuildFormWithoutReferenceField()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $formType->buildView($formView, $form, ['view' => new View()]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Required "reference_field" form option must be of type UniteCMS\CoreBundle\Entity\ContentTypeField.
     */
    public function testBuildFormWithInvalidReferenceField()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => 'foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ReferenceOf form type needs a content option on the root form element.
     */
    public function testBuildFormWithoutFormRoot()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $form = $this->createMock(Form::class);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => new ContentTypeField()]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ReferenceOf form type needs a content option on the root form element.
     */
    public function testBuildFormWithoutFormRootContent()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $rootForm = new Form(new FormConfigBuilder(0, null, $dispatcherMock, []));
        $form = $this->createMock(Form::class);
        $form->expects($this->any())->method('getRoot')->willReturn($rootForm);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => new ContentTypeField()]);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage ReferenceOf form type needs a content option on the root form element.
     */
    public function testBuildFormWithInvalidFormRootContent()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $rootForm = new Form(new FormConfigBuilder(0, null, $dispatcherMock, ['content' => 'foo']));
        $form = $this->createMock(Form::class);
        $form->expects($this->any())->method('getRoot')->willReturn($rootForm);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => new ContentTypeField()]);
    }

    public function testBuildFormWithEmptyRootContent()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $viewTypeManager->expects($this->any())->method('getViewType')->willReturn(
            new TableViewType($fieldTypeManager, new TableViewConfigurationFactory(100))
        );
        $viewParameterBag = new ViewParameterBag();
        $viewTypeManager->expects($this->any())->method('getTemplateRenderParameters')->willReturn($viewParameterBag);
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $content = new Content();
        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $rootForm = new Form(new FormConfigBuilder(0, null, $dispatcherMock, ['content' => $content]));
        $form = $this->createMock(Form::class);
        $form->expects($this->any())->method('getRoot')->willReturn($rootForm);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => new ContentTypeField()]);
        $this->assertNull($formView->vars['template']);
        $this->assertNull($formView->vars['templateParameters']);
    }

    public function testBuildFormWithRootContent()
    {
        $viewTypeManager = $this->createMock(ViewTypeManager::class);
        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $viewTypeManager->expects($this->any())->method('getViewType')->willReturn(
            new TableViewType($fieldTypeManager, new TableViewConfigurationFactory(100))
        );
        $viewParameterBag = new ViewParameterBag();
        $viewTypeManager->expects($this->any())->method('getTemplateRenderParameters')->willReturn($viewParameterBag);
        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);

        $content = $this->createMock(Content::class);
        $content->expects($this->any())->method('getId')->willReturn('XXX-YYY-ZZZ');

        $formType = new ReferenceOfType($viewTypeManager);
        $formView = new FormView();
        $rootForm = new Form(new FormConfigBuilder(0, null, $dispatcherMock, ['content' => $content]));
        $reference_field = new ContentTypeField();
        $reference_field->setIdentifier('ref');

        $form = $this->createMock(Form::class);
        $form->expects($this->any())->method('getRoot')->willReturn($rootForm);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => $reference_field]);
        $this->assertArrayHasKey('template', $formView->vars);
        $this->assertArrayHasKey('templateParameters', $formView->vars);
        $this->assertEquals("UniteCMSCoreBundle:Views:Table/index.html.twig", $formView->vars['template']);
        $templateParamSettings = $formView->vars['templateParameters']->getSettings();
        $this->assertEquals(true, $templateParamSettings['embedded']);
        $this->assertEquals([
            'field' => 'ref.content',
            'operator' => '=',
            'value' => $content->getId(),
        ], $templateParamSettings['filter']);

        // Test filter appending, if filter in view is not empty
        $viewParameterBag->setSettings(['filter' => ['field' => 'foo', 'operator' => '=', 'value' => 'baa']]);
        $formType->buildView($formView, $form, ['view' => new View(), 'reference_field' => $reference_field]);
        $templateParamSettings = $formView->vars['templateParameters']->getSettings();
        $this->assertEquals(['AND' => [
            ['field' => 'ref.content', 'operator' => '=', 'value' => $content->getId()],
            ['field' => 'foo', 'operator' => '=', 'value' => 'baa'],
        ]], $templateParamSettings['filter']);

    }
}
