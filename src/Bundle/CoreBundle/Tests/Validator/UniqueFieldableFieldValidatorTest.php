<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableField;
use UniteCMS\CoreBundle\Validator\Constraints\UniqueFieldableFieldValidator;

class UniqueFieldableFieldValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = UniqueFieldableField::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The UniqueFieldableFieldValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableField value.
     */
    public function testInvalidValue() {
        // Validate value.
        $this->validate((object)[], new UniqueFieldableFieldValidator());
    }
}
