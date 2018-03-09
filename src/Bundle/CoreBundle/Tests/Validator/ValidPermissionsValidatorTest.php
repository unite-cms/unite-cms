<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidPermissions;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidPermissionsValidator;

class ValidPermissionsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintValidatorClass = ValidPermissionsValidator::class;

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testInvalidAttributesCallback() {
        $object = new class {
            public function getRoles() { return[]; }
            public function getAttributes() { return[]; }
        };

        $this->validate([], null, new ValidPermissions([
            'callbackRoles' => 'getRoles',
            'callbackAttributes' => 'baa',
        ]), $object);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testInvalidRolesCallback() {
        $object = new class {
            public function getRoles() { return[]; }
            public function getAttributes() { return[]; }
        };

        $this->validate([], null, new ValidPermissions([
            'callbackRoles' => 'foo',
            'callbackAttributes' => 'getAttributes',
        ]), $object);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidValueStructure() {
        $object = new class {
            public function getRoles() { return ['a', 'b']; }
            public function getAttributes() { return ['a', 'b']; }
        };

        $this->validate(['a' => 'a', 'b' => 'b'], null, new ValidPermissions([
            'callbackRoles' => 'getRoles',
            'callbackAttributes' => 'getAttributes',
        ]), $object);
    }

    public function testInvalidValue() {
        $object = new class {
            public function getRoles() { return ['a']; }
            public function getAttributes() { return ['a']; }
        };

        $context = $this->validate(['a' => ['b']], null, new ValidPermissions([
            'callbackRoles' => 'getRoles',
            'callbackAttributes' => 'getAttributes',
        ]), $object);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('Invalid permissions or roles where selected.', $context->getViolations()->get(0)->getMessageTemplate());

        $context = $this->validate(['b' => ['a']], null, new ValidPermissions([
            'callbackRoles' => 'getRoles',
            'callbackAttributes' => 'getAttributes',
        ]), $object);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('Invalid permissions or roles where selected.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValueStructure() {
        $object = new class {
            public function getRoles() { return ['a', 'b']; }
            public function getAttributes() { return ['a', 'b']; }
        };

        $context = $this->validate(['a' => ['a', 'b'], 'b' => ['a', 'b']], null, new ValidPermissions([
            'callbackRoles' => 'getRoles',
            'callbackAttributes' => 'getAttributes',
        ]), $object);
        $this->assertCount(0, $context->getViolations());
    }
}