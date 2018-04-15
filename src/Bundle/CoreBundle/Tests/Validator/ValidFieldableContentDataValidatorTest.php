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
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;

class ValidFieldableContentDataValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidFieldableContentData::class;


    public function testEmptyObjectAndContextObject()
    {
        $object = new \stdClass();
        $fieldTypeManager = $this->createMock(FieldTypeManager::class);

        // When validation a non-array or don't provide a context object, the validator just skips this.
        $context = $this->validate(null, new ValidFieldableContentDataValidator($fieldTypeManager), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate((object)[], new ValidFieldableContentDataValidator($fieldTypeManager), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate([], new ValidFieldableContentDataValidator($fieldTypeManager));
        $this->assertCount(0, $context->getViolations());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldableContentDataValidator constraint expects a UniteCMS\CoreBundle\Entity\FieldableContent object.
     */
    public function testInvalidObject()
    {
        $object = new \stdClass();
        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $this->validate([], new ValidFieldableContentDataValidator($fieldTypeManager), null, $object);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidFieldableContentDataValidator constraint expects object->getEntity() to return a UniteCMS\CoreBundle\Entity\Fieldable object.
     */
    public function testInvalidObjectReference()
    {
        $object = $this->createMock(FieldableContent::class);
        $object->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $this->validate([], new ValidFieldableContentDataValidator($fieldTypeManager), null, $object);
    }

    public function testInvalidAdditionalValue()
    {

        $ct = new ContentType();
        $f1 = new ContentTypeField();
        $f1->setIdentifier('f1');
        $ct->addField($f1);
        $content = new Content();
        $content->setContentType($ct);

        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $context = $this->validate(['f1' => 'foo', 'f2' => 'baa'], new ValidFieldableContentDataValidator($fieldTypeManager), null, $content);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The content unit contains invalid additional data.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testInvalidDataValue()
    {

        $ct = new ContentType();
        $f1 = new ContentTypeField();
        $f1->setIdentifier('f1');
        $ct->addField($f1);
        $content = new Content();
        $content->setContentType($ct);

        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $fieldTypeManager->expects($this->any())
            ->method('validateFieldData')
            ->willReturn([
                new ConstraintViolation('m1', 'm1', [], 'root', 'root', 'i1'),
                new ConstraintViolation('m2', 'm2', [], 'root', 'root', 'i2'),
            ]);

        $context = $this->validate(['f1' => 'foo'], new ValidFieldableContentDataValidator($fieldTypeManager), null, $content);
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());
    }

    public function testValidDataValue()
    {

        $ct = new ContentType();
        $f1 = new ContentTypeField();
        $f1->setIdentifier('f1');
        $ct->addField($f1);
        $content = new Content();
        $content->setContentType($ct);

        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $fieldTypeManager->expects($this->any())
            ->method('validateFieldData')
            ->willReturn([]);

        $context = $this->validate(['f1' => 'foo'], new ValidFieldableContentDataValidator($fieldTypeManager), null, $content);
        $this->assertCount(0, $context->getViolations());
    }
}
