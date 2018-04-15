<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\DefaultViewType;
use UniteCMS\CoreBundle\Validator\Constraints\DefaultViewTypeValidator;

class DefaultViewTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = DefaultViewType::class;
    protected $constraintValidatorClass = DefaultViewTypeValidator::class;

    public function testInvalidValue()
    {
        $context = $this->validate([]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default view type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidViewValue()
    {
        $c = new View();
        $c->setIdentifier(View::DEFAULT_VIEW_IDENTIFIER);
        $context = $this->validate([$c]);
        $this->assertCount(0, $context->getViolations());
    }

    public function testInValidViewValue()
    {
        $c = new View();
        $c->setIdentifier('any_other');
        $context = $this->validate([$c]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default view type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }
}
