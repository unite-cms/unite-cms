<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.07.18
 * Time: 10:20
 */

namespace UniteCMS\CoreBundle\Tests\Validator;

use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidValidations;
use UniteCMS\CoreBundle\Validator\Constraints\ValidValidationsValidator;

class ValidValidationsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintValidatorClass = ValidValidationsValidator::class;
    protected $constraintClass = ValidValidations::class;

    public function testInvalidValidViolationsValidator() {

        // Non array violations are not valid.
        $violations = $this->validate(new \stdClass())->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate(new FieldableValidation('true'))->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate('XXX')->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        // Empty validation expression.
        $violations = $this->validate([new FieldableValidation('')])->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate([new FieldableValidation('')])->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate([new FieldableValidation('invalid')])->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate([new FieldableValidation('true'), new FieldableValidation('invalid')])->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[1][expression]', $violations->get(0)->getPropertyPath());

        $violations = $this->validate([new FieldableValidation('true'), new FieldableValidation('true', 'message', 'path', ['UNKNOWN'])])->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[1][group]', $violations->get(0)->getPropertyPath());
    }

    public function testValidValidViolationsValidator() {

        // Empty violations are valid.
        $this->assertCount(0, $this->validate([])->getViolations());

        // Array of valid validation definitions are valid.
        $this->assertCount(0, $this->validate([ new FieldableValidation('true') ])->getViolations());
        $this->assertCount(0, $this->validate([ new FieldableValidation('1 == 2 or "a" in ["b", "c"]') ])->getViolations());
        $this->assertCount(0, $this->validate([ new FieldableValidation('true', '', '', ['DELETE', 'CREATE', 'UPDATE']) ])->getViolations());
    }
}