<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Fieldable;
use UnitedCMS\CoreBundle\Entity\FieldableContent;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocaleValidator;

class ValidFieldableContentLocaleValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidFieldableContentLocale::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldableContentLocaleValidator constraint expects a UnitedCMS\CoreBundle\Entity\FieldableContent object.
     */
    public function testInvalidObject() {
        $object = new \stdClass();
        $this->validate((object)[], new ValidFieldableContentLocaleValidator(), null, $object);
    }

    public function testEmptyObjectAndContextObject() {
        $object = new Content();

        // When validating an empty value or don't provide a context object, the validator just skips this.
        $context = $this->validate(null, new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate((object)[], new ValidFieldableContentLocaleValidator());
        $this->assertCount(0, $context->getViolations());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldableContentLocaleValidator constraint expects object->getEntity() to return a UnitedCMS\CoreBundle\Entity\Fieldable object.
     */
    public function testInvalidObjectReference() {
        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->validate('', new ValidFieldableContentLocaleValidator(), null, $object);
    }

    public function testEmptyEntity() {
        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn(null);

        $errors = $this->validate('', new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());

        $errors = $this->validate('en', new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('This locale is not supported by this content type', $errors->getViolations()->get(0)->getMessageTemplate());
    }

    public function testEmptyEntityLocales() {
        $entity = $this->createMock(Fieldable::class);
        $entity->expects($this->any())
            ->method('getLocales')
            ->willReturn([]);

        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);

        $errors = $this->validate('', new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());

        $errors = $this->validate('en', new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('This locale is not supported by this content type', $errors->getViolations()->get(0)->getMessageTemplate());
    }

    public function testEmptyLocale() {
        $entity = $this->createMock(Fieldable::class);
        $entity->expects($this->any())
            ->method('getLocales')
            ->willReturn(['de']);

        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);

        $errors = $this->validate(null, new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());
    }

    public function testInvalidLocale() {
        $entity = $this->createMock(Fieldable::class);
        $entity->expects($this->any())
            ->method('getLocales')
            ->willReturn(['de']);

        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);

        $errors = $this->validate('en', new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('This locale is not supported by this content type', $errors->getViolations()->get(0)->getMessageTemplate());

        $errors = $this->validate('de', new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());
    }
}