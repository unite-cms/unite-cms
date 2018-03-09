<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use UnitedCMS\CoreBundle\View\ViewTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ViewType;
use UnitedCMS\CoreBundle\Validator\Constraints\ViewTypeValidator;

class ViewTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ViewType::class;

    public function testInvalidValue() {

        // Create validator with mocked viewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);

        // Validate value.
        $context = $this->validate('any_wrong_value', new ViewTypeValidator($viewTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered view type.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidValue() {

        // Create validator with mocked viewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);
        $viewTypeManagerMock->expects($this->any())
            ->method('hasViewType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate('any_wrong_value', new ViewTypeValidator($viewTypeManagerMock));
        $this->assertCount(0, $context->getViolations());
    }

    public function testNonStringValue() {

        // Create validator with mocked viewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);
        $viewTypeManagerMock->expects($this->any())
            ->method('hasViewType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(1, new ViewTypeValidator($viewTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered view type.', $context->getViolations()->get(0)->getMessageTemplate());
    }
}