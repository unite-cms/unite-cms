<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UniteCMS\CoreBundle\Validator\Constraints\ReservedWordsValidator;

class ReservedWordsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ReservedWords::class;
    protected $constraintValidatorClass = ReservedWordsValidator::class;

    const RESERVED = ['const_reserved'];

    public function testInvalidValue() {
        $constraint = new ReservedWords(['reserved' => ['reserved']]);
        $context = $this->validate('reserved', null, $constraint);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The value is in the list of reserved words.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidValue() {
        $constraint = new ReservedWords(['reserved' => ['reserved']]);
        $context = $this->validate('other', null, $constraint);
        $this->assertCount(0, $context->getViolations());

        $constraint = new ReservedWords(['reserved' => []]);
        $context = $this->validate('other', null, $constraint);
        $this->assertCount(0, $context->getViolations());
    }

    public function testInvalidConstValue() {
        $constraint = new ReservedWords(['reserved' => self::class . '::' . 'RESERVED']);
        $context = $this->validate('const_reserved', null, $constraint);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The value is in the list of reserved words.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidConstValue() {
        $constraint = new ReservedWords(['reserved' => self::class . '::' . 'RESERVED']);
        $context = $this->validate('other', null, $constraint);
        $this->assertCount(0, $context->getViolations());
    }
}
