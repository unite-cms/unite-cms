<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\View\ViewType;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidViewSettings;
use UniteCMS\CoreBundle\Validator\Constraints\ValidViewSettingsValidator;

class ValidViewSettingsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidViewSettings::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidViewSettingsValidator constraint expects a UniteCMS\CoreBundle\View\ViewSettings value.
     */
    public function testNonContentValue() {
        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);

        // Validate value.
        $this->validate((object)[], new ValidViewSettingsValidator($viewTypeManagerMock));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidViewSettingsValidator constraint expects a UniteCMS\CoreBundle\Entity\View object.
     */
    public function testNonContextObject() {
        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);

        // Validate value.
        $this->validate(new ViewSettings(), new ValidViewSettingsValidator($viewTypeManagerMock));
    }

    public function testInvalidValue() {

        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = new ViewTypeManager($this->createMock(UrlGenerator::class));
        $viewTypeManagerMock->registerViewType(new class extends ViewType {
            const TYPE = "type";
            public function validateSettings(ViewSettings $settings, ExecutionContextInterface $context)
            {
                $context->buildViolation('m1')->addViolation();
                $context->buildViolation('m2')->addViolation();
            }
        });

        $view = new View();
        $view->setType('type');

        // Validate value.
        $context = $this->validate(new ViewSettings(), new ValidViewSettingsValidator($viewTypeManagerMock), null, $view);
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());
    }

    public function testValidValue() {

        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = new ViewTypeManager($this->createMock(UrlGenerator::class));
        $viewTypeManagerMock->registerViewType(new class extends ViewType {
            const TYPE = "type";
            public function validateSettings(ViewSettings $settings, ExecutionContextInterface $context) {}
        });

        $view = new View();
        $view->setType('type');

        // Validate value.
        $context = $this->validate(new ViewSettings(), new ValidViewSettingsValidator($viewTypeManagerMock), null, $view);
        $this->assertCount(0, $context->getViolations());
    }
}
