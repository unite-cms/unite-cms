<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\FieldType;
use UniteCMS\CoreBundle\Validator\Constraints\FieldTypeValidator;

class FieldTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = FieldType::class;

    public function testInvalidValue()
    {

        // Create validator with mocked fieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);

        // Validate value.
        $context = $this->validate('any_wrong_value', new FieldTypeValidator($fieldTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered field type.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidValue()
    {

        // Create validator with mocked fieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);
        $fieldTypeManagerMock->expects($this->any())
            ->method('hasFieldType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate('any_wrong_value', new FieldTypeValidator($fieldTypeManagerMock));
        $this->assertCount(0, $context->getViolations());
    }

    public function testNonStringValue()
    {

        // Create validator with mocked fieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);
        $fieldTypeManagerMock->expects($this->any())
            ->method('hasFieldType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(1, new FieldTypeValidator($fieldTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered field type.', $context->getViolations()->get(0)->getMessageTemplate());
    }
}
