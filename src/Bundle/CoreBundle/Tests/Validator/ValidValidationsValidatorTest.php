<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.07.18
 * Time: 10:20
 */

namespace UniteCMS\CoreBundle\Tests\Validator;

use Doctrine\ORM\EntityManagerInterface;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidValidations;
use UniteCMS\CoreBundle\Validator\Constraints\ValidValidationsValidator;

class ValidValidationsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidValidations::class;

    /**
     * @var ValidValidationsValidator $validator
     */
    protected $validator;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->validator = new ValidValidationsValidator($this->createMock(EntityManagerInterface::class));
    }

    public function testInvalidValidViolationsValidator() {

        // Non array violations are not valid.
        $violations = $this->validate(new \stdClass(), $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate(new FieldableValidation('true'), $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate('XXX', $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        // Empty validation expression.
        $violations = $this->validate([new FieldableValidation('')], $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate([new FieldableValidation('')], $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate([new FieldableValidation('invalid')], $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());

        $violations = $this->validate([new FieldableValidation('true'), new FieldableValidation('invalid')], $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[1][expression]', $violations->get(0)->getPropertyPath());

        $violations = $this->validate([new FieldableValidation('true'), new FieldableValidation('true', 'message', 'path', ['UNKNOWN'])], $this->validator)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('Invalid validation definition found.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[1][group]', $violations->get(0)->getPropertyPath());
    }

    public function testValidValidViolationsValidator() {

        // Empty violations are valid.
        $this->assertCount(0, $this->validate([], $this->validator)->getViolations());

        // Array of valid validation definitions are valid.
        $this->assertCount(0, $this->validate([ new FieldableValidation('true') ], $this->validator)->getViolations());
        $this->assertCount(0, $this->validate([ new FieldableValidation('1 == 2 or "a" in ["b", "c"]') ], $this->validator)->getViolations());
        $this->assertCount(0, $this->validate([ new FieldableValidation('true', '', '', ['DELETE', 'CREATE', 'UPDATE']) ], $this->validator)->getViolations());

        // Make sure, that doctrine content functions are available during validation.
        $this->assertCount(0, $this->validate([ new FieldableValidation('content_unique("Foo", "baa")') ], $this->validator)->getViolations());
        $this->assertCount(0, $this->validate([ new FieldableValidation('content_uniquify("Foo", "baa")') ], $this->validator)->getViolations());
    }
}