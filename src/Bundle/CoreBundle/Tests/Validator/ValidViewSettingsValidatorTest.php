<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\View\ViewSettings;
use UnitedCMS\CoreBundle\View\ViewTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidViewSettings;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidViewSettingsValidator;

class ValidViewSettingsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidViewSettings::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidViewSettingsValidator constraint expects a UnitedCMS\CoreBundle\View\ViewSettings value.
     */
    public function testNonContentValue() {
        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);

        // Validate value.
        $this->validate((object)[], new ValidViewSettingsValidator($viewTypeManagerMock));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidViewSettingsValidator constraint expects a UnitedCMS\CoreBundle\Entity\View object.
     */
    public function testNonContextObject() {
        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);

        // Validate value.
        $this->validate(new ViewSettings(), new ValidViewSettingsValidator($viewTypeManagerMock));
    }

    public function testInvalidValue() {
        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);
        $viewTypeManagerMock->expects($this->any())
            ->method('validateViewSettings')
            ->willReturn([
                new ConstraintViolation('m1', 'm1', [], 'root', 'root', 'i1'),
                new ConstraintViolation('m2', 'm2', [], 'root', 'root', 'i2'),
            ]);
        $viewTypeManagerMock->expects($this->any())
            ->method('hasViewType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(new ViewSettings(), new ValidViewSettingsValidator($viewTypeManagerMock), null, new View());
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());
    }

    public function testValidValue() {
        // Create validator with mocked ViewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);
        $viewTypeManagerMock->expects($this->any())
            ->method('validateViewSettings')
            ->willReturn([]);

        $viewTypeManagerMock->expects($this->any())
            ->method('hasViewType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(new ViewSettings(), new ValidViewSettingsValidator($viewTypeManagerMock), null, new View());
        $this->assertCount(0, $context->getViolations());
    }
}