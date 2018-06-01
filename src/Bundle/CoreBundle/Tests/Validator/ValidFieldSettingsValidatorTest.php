<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldSettingsValidator;

class ValidFieldSettingsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidFieldSettings::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldSettingsValidator constraint expects a UniteCMS\CoreBundle\Field\FieldableFieldSettings value.
     */
    public function testNonContentValue() {
        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);

        // Validate value.
        $this->validate((object)[], new ValidFieldSettingsValidator($fieldTypeManagerMock));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldSettingsValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableField object.
     */
    public function testInvalidContextObject() {
        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = $this->createMock(FieldTypeManager::class);

        // Validate valid value, but invalid context object.
        $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock));
    }

    public function testInvalidValue() {

        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = new FieldTypeManager();
        $fieldTypeManagerMock->registerFieldType(new class extends FieldType{
            const TYPE = "type";
            public function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
            {
                $context->buildViolation('m1')->addViolation();
                $context->buildViolation('m2')->addViolation();
            }
        });

        $field = new ContentTypeField();
        $field->setType('type');

        // Validate value.
        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, $field);
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());

        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, $field);
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());
    }

    public function testValidValue() {

        // Create validator with mocked FieldTypeManager.
        $fieldTypeManagerMock = new FieldTypeManager();
        $fieldTypeManagerMock->registerFieldType(new class extends FieldType{
            const TYPE = "type";
            public function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context) {}
        });

        $field = new ContentTypeField();
        $field->setType('type');

        // Validate value.
        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, $field);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate(new FieldableFieldSettings(), new ValidFieldSettingsValidator($fieldTypeManagerMock), null, $field);
        $this->assertCount(0, $context->getViolations());
    }
}
