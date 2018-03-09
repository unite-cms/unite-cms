<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\SettingTypeField;
use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldSettingsValidator;

class ValidFieldSettingsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidFieldSettings::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldSettingsValidator constraint expects a UnitedCMS\CoreBundle\Field\FieldableFieldSettings value.
     */
    public function testNonContentValue() {
        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);

        // Validate value.
        $this->validate((object)[], new ValidFieldSettingsValidator($fieldTypeManagerMock));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldSettingsValidator constraint expects a UnitedCMS\CoreBundle\Entity\FieldableField object.
     */
    public function testInvalidContextObject() {
        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);

        // Validate valid value, but invalid context object.
        $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock));
    }

    public function testInvalidValue() {
        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);
        $fieldTypeManagerMock->expects($this->any())
            ->method('validateFieldSettings')
            ->willReturn([
                new ConstraintViolation('m1', 'm1', [], 'root', 'root', 'i1'),
                new ConstraintViolation('m2', 'm2', [], 'root', 'root', 'i2'),
            ]);
        $fieldTypeManagerMock->expects($this->any())
            ->method('hasFieldType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, new ContentTypeField());
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());

        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, new SettingTypeField());
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());
    }

    public function testValidValue() {
        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);
        $fieldTypeManagerMock->expects($this->any())
            ->method('validateFieldSettings')
            ->willReturn([]);

        $fieldTypeManagerMock->expects($this->any())
            ->method('hasFieldType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, new ContentTypeField());
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, new SettingTypeField());
        $this->assertCount(0, $context->getViolations());
    }
}