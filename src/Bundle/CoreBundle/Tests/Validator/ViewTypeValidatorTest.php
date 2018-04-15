<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ViewType;
use UniteCMS\CoreBundle\Validator\Constraints\ViewTypeValidator;

class ViewTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ViewType::class;

    public function testInvalidValue()
    {

        // Create validator with mocked viewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);

        // Validate value.
        $context = $this->validate('any_wrong_value', new ViewTypeValidator($viewTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered view type.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidValue()
    {

        // Create validator with mocked viewTypeManager.
        $viewTypeManagerMock = $this->createMock(ViewTypeManager::class);
        $viewTypeManagerMock->expects($this->any())
            ->method('hasViewType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate('any_wrong_value', new ViewTypeValidator($viewTypeManagerMock));
        $this->assertCount(0, $context->getViolations());
    }

    public function testNonStringValue()
    {

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
