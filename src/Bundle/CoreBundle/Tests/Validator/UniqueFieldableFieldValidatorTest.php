<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UnitedCMS\CoreBundle\Validator\Constraints\UniqueFieldableFieldValidator;

class UniqueFieldableFieldValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = UniqueFieldableField::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The UniqueFieldableFieldValidator constraint expects a UnitedCMS\CoreBundle\Entity\FieldableField value.
     */
    public function testInvalidValue() {
        // Validate value.
        $this->validate((object)[], new UniqueFieldableFieldValidator());
    }
}