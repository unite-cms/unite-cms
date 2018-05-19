<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissions;
use UniteCMS\CoreBundle\Validator\Constraints\ValidPermissionsValidator;

class ValidPermissionsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintValidatorClass = ValidPermissionsValidator::class;

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testInvalidAttributesCallback() {
        $object = new class {
            public function getAttributes() { return[]; }
        };

        $this->validate([], null, new ValidPermissions([
            'callbackAttributes' => 'baa',
        ]), $object);
    }

    public function testInvalidValue() {
        $object = new class {
            public function getAttributes() { return ['a']; }
        };

        $context = $this->validate(['a' => '(('], null, new ValidPermissions([
            'callbackAttributes' => 'getAttributes',
        ]), $object);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('Unsupported permissions or invalid expressions found.', $context->getViolations()->get(0)->getMessageTemplate());

        $context = $this->validate(['b' => 'true'], null, new ValidPermissions([
            'callbackAttributes' => 'getAttributes',
        ]), $object);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('Unsupported permissions or invalid expressions found.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValueStructure() {
        $object = new class {
            public function getAttributes() { return ['a', 'b']; }
        };

        $context = $this->validate(['a' => 'true', 'b' => '1 == 1'], null, new ValidPermissions([
            'callbackAttributes' => 'getAttributes',
        ]), $object);
        $this->assertCount(0, $context->getViolations());
    }
}
