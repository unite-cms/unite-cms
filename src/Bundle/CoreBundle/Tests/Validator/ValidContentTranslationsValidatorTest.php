<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Fieldable;
use UnitedCMS\CoreBundle\Entity\FieldableContent;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslations;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationsValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocaleValidator;

class ValidContentTranslationsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidContentTranslations::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidContentTranslationsValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content object.
     */
    public function testInvalidObject() {
        $object = new \stdClass();
        $this->validate((object)[], new ValidContentTranslationsValidator(), null, $object);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidContentTranslationsValidator constraint expects an array or a Doctrine\Common\Collections\Collection value.
     */
    public function testInvalidValue() {
        $object = new Content();
        $this->validate((object)[], new ValidContentTranslationsValidator(), null, $object);
    }

    public function testEmptyObjectAndContextObject() {
        $object = new Content();

        // When validating an empty value or don't provide a context object, the validator just skips this.
        $context = $this->validate(null, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate((object)[], new ValidContentTranslationsValidator());
        $this->assertCount(0, $context->getViolations());
    }

    public function testDuplicatedLocaleInTranslations() {
        $object = new Content();
        $translations = new ArrayCollection([new Content(), new Content()]);
        $translations->get(0)->setLocale('de')->setTranslationof($object);
        $translations->get(1)->setLocale('de')->setTranslationof($object);
        $errors = $this->validate($translations, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('There are two ore more translations in the same language.', $errors->getViolations()->get(0)->getMessageTemplate());

        $translations->get(1)->setLocale('en')->setTranslationof($object);
        $errors = $this->validate($translations, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());

        // Also the reference content cannot have the same locale as one of the translations.
        $object->setLocale('en');
        $errors = $this->validate($translations, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('There are two ore more translations in the same language.', $errors->getViolations()->get(0)->getMessageTemplate());
    }

    public function testNestedTranslations() {
        $object = $this->createMock(Content::class);
        $translations = new ArrayCollection([new Content()]);
        $translations->get(0)->setLocale('de')->setTranslationof($object)->setTranslations(new ArrayCollection([new Content()]));
        $errors = $this->validate($translations, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('Translations cannot have other content as translation.', $errors->getViolations()->get(0)->getMessageTemplate());
    }

    public function testInvalidTranslationOf() {
        $object = $this->createMock(Content::class);
        $translations = new ArrayCollection([new Content()]);
        $translations->get(0)->setLocale('de')->setTranslationof(new Content());
        $errors = $this->validate($translations, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('Translations cannot have other content as translation.', $errors->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidTranslations() {
        $object = new Content();
        $object->setLocale('de');
        $translations = new ArrayCollection([new Content(), new Content()]);
        $translations->get(0)->setLocale('en')->setTranslationof($object);
        $translations->get(1)->setLocale('fr')->setTranslationof($object);
        $errors = $this->validate($translations, new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());

        // Also setting an array for translations should be valid.
        $errors = $this->validate($translations->toArray(), new ValidContentTranslationsValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());
    }
}