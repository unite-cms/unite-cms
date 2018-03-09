<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\DefaultViewType;
use UnitedCMS\CoreBundle\Validator\Constraints\DefaultViewTypeValidator;

class DefaultViewTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = DefaultViewType::class;
    protected $constraintValidatorClass = DefaultViewTypeValidator::class;

    public function testInvalidValue() {
        $context = $this->validate([]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default view type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidViewValue() {
        $c = new View();
        $c->setIdentifier(View::DEFAULT_VIEW_IDENTIFIER);
        $context = $this->validate([$c]);
        $this->assertCount(0, $context->getViolations());
    }

    public function testInValidViewValue() {
        $c = new View();
        $c->setIdentifier('any_other');
        $context = $this->validate([$c]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default view type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }
}