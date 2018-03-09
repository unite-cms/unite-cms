<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\FilterCollection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Fieldable;
use UnitedCMS\CoreBundle\Entity\FieldableContent;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationOf;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationOfValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslations;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationsValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocaleValidator;

class ValidContentTranslationOfValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidContentTranslationOf::class;

    private $em;

    public function setUp()
    {
        parent::setUp();
        $filtersMock = $this->createMock(FilterCollection::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->em->expects($this->any())
                ->method('getFilters')
                ->willReturn($filtersMock);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidContentTranslationOfValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content object.
     */
    public function testInvalidObject() {
        $object = new \stdClass();
        $this->validate((object)[], new ValidContentTranslationOfValidator($this->em), null, $object);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidContentTranslationOfValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content value.
     */
    public function testInvalidValue() {
        $object = new Content();
        $this->validate((object)[], new ValidContentTranslationOfValidator($this->em), null, $object);
    }

    public function testEmptyObjectAndContextObject() {
        $object = new Content();

        // When validating an empty value or don't provide a context object, the validator just skips this.
        $context = $this->validate(null, new ValidContentTranslationOfValidator($this->em), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate(new Content(), new ValidContentTranslationOfValidator($this->em));
        $this->assertCount(0, $context->getViolations());
    }

    public function testDuplicatedLocale() {
        $object = new Content();
        $object->setLocale('de');
        $value = new Content();
        $value->setLocale('de');

        $errors = $this->validate($value, new ValidContentTranslationOfValidator($this->em), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('There are two ore more translations in the same language.', $errors->getViolations()->get(0)->getMessageTemplate());

        $object->setLocale('en');
        $errors = $this->validate($value, new ValidContentTranslationOfValidator($this->em), null, $object);
        $this->assertCount(0, $errors->getViolations());
    }
}