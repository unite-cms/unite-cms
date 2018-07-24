<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\ConstraintViolationList;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;

class ValidFieldableContentDataCustomValidationsTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidFieldableContentData::class;

    /**
     * @var ValidFieldableContentDataValidator
     */
    protected $constraintValidator;

    /**
     * @var ContentType
     */
    protected $contentType;

    /**
     * @var Content
     */
    protected $content;

    /**
     * @var SettingType
     */
    protected $settingType;

    /**
     * @var Setting
     */
    protected $setting;


    public function setUp()
    {
        parent::setUp();

        $this->constraintValidator = new ValidFieldableContentDataValidator($this->createMock(FieldTypeManager::class));

        $this->contentType = new ContentType();
        $this->contentType->setTitle('Content Type 1')->setIdentifier('ct1');

        $contentTypeFieldTitle = new ContentTypeField();
        $contentTypeFieldTitle->setTitle('title')->setIdentifier('title')->setType('text');
        $this->contentType->addField($contentTypeFieldTitle);

        $contentTypeFieldReference = new ContentTypeField();
        $contentTypeFieldReference->setTitle('reference')->setIdentifier('reference')->setType('reference');
        $this->contentType->addField($contentTypeFieldReference);

        $this->content = new Content();
        $this->content->setContentType($this->contentType);

        $this->settingType = new SettingType();
        $this->settingType->setTitle('Setting Type 1')->setIdentifier('st1');

        $settingTypeFieldTitle = new SettingTypeField();
        $settingTypeFieldTitle->setTitle('title')->setIdentifier('title')->setType('text');
        $this->settingType->addField($settingTypeFieldTitle);

        $settingTypeFieldReference = new SettingTypeField();
        $settingTypeFieldReference->setTitle('reference')->setIdentifier('reference')->setType('reference');
        $this->settingType->addField($settingTypeFieldReference);

        $this->setting = new Setting();
        $this->setting->setSettingType($this->settingType);
    }

    /**
     * @param array $data
     * @param array $validations
     * @param string $locale
     * @param string $group
     * @return ConstraintViolationList
     */
    protected function validateValidations(array $data, array $validations = [], string $locale = null, string $group = null) {
        $this->contentType->setValidations($validations);
        $this->content->setData($data);

        if($locale) {
            $this->content->setLocale($locale);
        }
        return $this->validate($this->content->getData(), $this->constraintValidator, null, $this->content, $group)->getViolations();
    }

    public function testSimpleSettingValidation() {

        // Note: We should test at least one setting validation, to test correct integration.
        //All other tests are only targeting the content type.
        //
        $validations = [
            new FieldableValidation("data.title != ''", 'This field is required.', 'title'),
        ];

        $this->settingType->setValidations($validations);
        $this->setting->setData([]);
        $violations = $this->validate($this->setting->getData(), $this->constraintValidator, null, $this->setting)->getViolations();
        $this->assertCount(1, $violations);
        $this->assertEquals('This field is required.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[title]', $violations->get(0)->getPropertyPath());

        $this->setting->setData(['title' => 'Foo']);
        $violations = $this->validate($this->setting->getData(), $this->constraintValidator, null, $this->setting)->getViolations();
        $this->assertCount(0, $violations);
    }

    public function testSimpleValidation() {

        $validations = [
            new FieldableValidation("data.title != ''", 'This field is required.', 'title'),
        ];

        $violations = $this->validateValidations([], $validations);
        $this->assertCount(1, $violations);
        $this->assertEquals('This field is required.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[title]', $violations->get(0)->getPropertyPath());

        $this->assertCount(0, $this->validateValidations(['title' => 'Foo'], $validations));
    }

    public function testLocaleValidation() {

        $validations = [
            new FieldableValidation("locale != 'en'"),
        ];

        $this->assertCount(0, $this->validateValidations([], $validations));
        $this->content->setLocale('de');
        $this->assertCount(0, $this->validateValidations([], $validations));
        $this->content->setLocale('en');
        $this->assertCount(1, $this->validateValidations([], $validations));

    }

    public function testNestedAttributeValidation() {

        $validations = [
            new FieldableValidation("data.reference.id == 'a'", 'Id must start with char.', 'reference.id'),
        ];

        $violations = $this->validateValidations([], $validations);
        $this->assertCount(1, $violations);
        $this->assertEquals('Id must start with char.', $violations->get(0)->getMessageTemplate());
        $this->assertEquals('[reference][id]', $violations->get(0)->getPropertyPath());

        $this->assertCount(1, $this->validateValidations(['reference' => ['id' => 'b']], $validations));
        $this->assertCount(0, $this->validateValidations(['reference' => ['id' => 'a']], $validations));
    }

    public function testValidationGroupValidation() {

        $validations = [
            new FieldableValidation("data.title == 'create'", '', '', ['CREATE']),
            new FieldableValidation("data.title == 'update'", '', '', ['UPDATE']),
            new FieldableValidation("data.title == 'delete'", '', '', ['DELETE']),
        ];

        $this->assertCount(0, $this->validateValidations(['title' => 'create'], $validations));

        $id = new \ReflectionProperty(Content::class, 'id');
        $id->setAccessible(true);
        $id->setValue($this->content, 1);

        $this->assertCount(0, $this->validateValidations(['title' => 'update'], $validations, null));
        $this->assertCount(0, $this->validateValidations(['title' => 'delete'], $validations, null, 'DELETE'));
    }

    public function testRegExValidation() {

        $validations = [
            new FieldableValidation("data.title matches '/^[a-z]+/'"),
        ];

        $this->assertCount(1, $this->validateValidations(['title' => '0abc'], $validations));
        $this->assertCount(1, $this->validateValidations(['title' => 'Abc'], $validations));
        $this->assertCount(0, $this->validateValidations(['title' => 'abcDEF'], $validations));
    }
}
