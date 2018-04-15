<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocaleValidator;

class ValidFieldableContentLocaleValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidFieldableContentLocale::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldableContentLocaleValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableContent object.
     */
    public function testInvalidObject()
    {
        $object = new \stdClass();
        $this->validate((object)[], new ValidFieldableContentLocaleValidator(), null, $object);
    }

    public function testEmptyObjectAndContextObject()
    {
        $object = new Content();

        // When validating an empty value or don't provide a context object, the validator just skips this.
        $context = $this->validate(null, new ValidFieldableContentLocaleValidator(), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate((object)[], new ValidFieldableContentLocaleValidator());
        $this->assertCount(0, $context->getViolations());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldableContentLocaleValidator constraint expects object->getEntity() to return a UniteCMS\CoreBundle\Entity\Fieldable object.
     */
    public function testInvalidObjectReference()
    {
        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->validate('', new ValidFieldableContentLocaleValidator(), null, $object);
    }

    public function testEmptyEntity()
    {
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

    public function testEmptyEntityLocales()
    {
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

    public function testEmptyLocale()
    {
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

    public function testInvalidLocale()
    {
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
