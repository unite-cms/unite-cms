<?php

namespace UniteCMS\CoreBundle\Tests\Entity;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ContentEntityPersistentTest extends DatabaseAwareTestCase
{

    public function testValidateContent()
    {

        // Try to validate empty content.
        $content = new Content();
        $errors = $this->container->get('validator')->validate($content);
        $this->assertCount(1, $errors);

        $this->assertEquals('contentType', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());
    }

    public function testValidateAdditionalContentData()
    {
        // 1. Create Content Type with 1 Field
        $ct = new ContentType();
        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('title')->setTitle('Title');
        $ct->setTitle('Ct1')->setIdentifier('ct1')->addField($field);

        // 2. Create Content1 with the same field. => VALID
        $content = new Content();
        $content->setContentType($ct)->setData(['title' => 'Title']);
        $this->assertCount(0, $this->container->get('validator')->validate($content));

        // 3. Create Content2 with the same field and another field. => INVALID
        $content->setData(array_merge($content->getData(), ['other' => "Other"]));
        $errors = $this->container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());

        // 4. Create Content2 with only another field. => INVALID
        $content->setData(['other' => 'Other']);
        $errors = $this->container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());

        // 5. ContentType have more fields than content. => VALID
        $field2 = new ContentTypeField();
        $field2->setType('text')->setIdentifier('title2')->setTitle('Title2');
        $ct->addField($field);
        $content->setContentType($ct)->setData(['title' => 'Title']);
        $this->assertCount(0, $this->container->get('validator')->validate($content));
    }

    public function testValidateContentDataValidation()
    {

        // 1. Create Content Type with 1 moked FieldType
        $mockedFieldType = new Class extends FieldType
        {
            const TYPE = "content_entity_test_mocked_field";

            function validateData(FieldableField $field, $data, $validation_group = 'DEFAULT'): array
            {
                if ($data && $validation_group !== 'DELETE') {
                    return [
                        new ConstraintViolation(
                            'mocked_message',
                            'mocked_message',
                            [],
                            $data,
                            'invalid',
                            $data
                        ),
                    ];
                }

                return [];
            }
        };

        // Inject the field type
        $this->container->get('unite.cms.field_type_manager')->registerFieldType($mockedFieldType);

        $ct = new ContentType();
        $field = new ContentTypeField();
        $field->setType('content_entity_test_mocked_field')->setIdentifier('invalid')->setTitle('Title');
        $ct->setTitle('Ct1')->setIdentifier('ct1')->addField($field);


        // 2. Create Content that is invalid with FieldType. => INVALID (at path)
        $content = new Content();
        $content->setContentType($ct)->setData(['invalid' => true]);
        $errors = $this->container->get('validator')->validate($content);
        $this->assertCount(1, $errors);
        $this->assertEquals('data.invalid', $errors->get(0)->getPropertyPath());
        $this->assertEquals('mocked_message', $errors->get(0)->getMessage());

        // 2.1 Validate DELETE on invalid content should be valid.
        $content = new Content();
        $content->setContentType($ct)->setData(['invalid' => true]);
        $this->assertCount(0, $this->container->get('validator')->validate($content, null, 'DELETE'));

        // 3. Create Content that is valid with FieldType. => VALID
        $content->setData(['invalid' => false]);
        $this->assertCount(0, $this->container->get('validator')->validate($content));
    }

    public function testValidateDeleteContentDataValidation()
    {

        // 1. Create Content Type with 1 moked FieldType
        $mockedFieldType = new Class extends FieldType
        {
            const TYPE = "content_entity_test_mocked_field";

            function validateData(FieldableField $field, $data, $validation_group = 'DEFAULT'): array
            {
                if ($data && $validation_group === 'DELETE') {
                    return [
                        new ConstraintViolation(
                            'mocked_message',
                            'mocked_message',
                            [],
                            $data,
                            'invalid',
                            $data
                        ),
                    ];
                }

                return [];
            }
        };

        // Inject the field type
        $this->container->get('unite.cms.field_type_manager')->registerFieldType($mockedFieldType);

        $ct = new ContentType();
        $field = new ContentTypeField();
        $field->setType('content_entity_test_mocked_field')->setIdentifier('invalid')->setTitle('Title');
        $ct->setTitle('Ct1')->setIdentifier('ct1')->addField($field);


        // 2. Create Content that is invalid with FieldType. => INVALID (at path)
        $content = new Content();
        $content->setContentType($ct)->setData(['invalid' => true]);
        $errors = $this->container->get('validator')->validate($content, null, ['DELETE']);
        $this->assertCount(1, $errors);
        $this->assertEquals('data.invalid', $errors->get(0)->getPropertyPath());
        $this->assertEquals('mocked_message', $errors->get(0)->getMessage());

        // 2.1 Validate DEFAULT on invalid content should be valid.
        $content = new Content();
        $content->setContentType($ct)->setData(['invalid' => true]);
        $this->assertCount(0, $this->container->get('validator')->validate($content));

        // 3. Create Content that is valid with FieldType. => VALID
        $content->setData(['invalid' => false]);
        $this->assertCount(0, $this->container->get('validator')->validate($content));
    }

    public function testContentEntityToStringMethod()
    {

        // Empty content should be printed as "".
        $this->assertEquals('Content', (string)new Content());

        // Content entity with id but no content_type should be printed as "Content #{id}".
        $content = new Content();
        $rp = new \ReflectionProperty($content, 'id');
        $rp->setAccessible(true);
        $rp->setValue($content, 'XXX-YYY-ZZZ');

        $this->assertEquals('Content #XXX-YYY-ZZZ', (string)$content);

        // Content with set content_type that has no defined content_label and no title should be the same.
        $contentType = new ContentType();
        $contentType->setContentLabel('');
        $content->setContentType($contentType);
        $this->assertEquals('Content #XXX-YYY-ZZZ', (string)$content);

        // Content with set content_type that has no defined content_label but a title should be "{ContentType} #{id}".
        $contentType->setTitle('News');
        $this->assertEquals('News #XXX-YYY-ZZZ', (string)$content);

        // Content with set content_type and defined content_label should interprets content_label.
        $contentType->setContentLabel("Foo");
        $this->assertEquals('Foo', (string)$content);

        $contentType->setContentLabel("#{id}");
        $this->assertEquals('#XXX-YYY-ZZZ', (string)$content);

        $content->setData(
            [
                'title' => 'My title',
                'foo' => 'baa',
                'nested' => [
                    'lu' => [
                        'la' => 'value',
                    ],
                ],
            ]
        );

        $contentType->setContentLabel("{title}");
        $this->assertEquals('My title', (string)$content);

        $contentType->setContentLabel("#{id} {foo}");
        $this->assertEquals('#XXX-YYY-ZZZ baa', (string)$content);

        $contentType->setContentLabel("{type}");
        $this->assertEquals('News', (string)$content);

        $contentType->setContentLabel("{nested.lu.la}");
        $this->assertEquals('value', (string)$content);

        $contentType->setContentLabel("{unknown}");
        $this->assertEquals('{unknown}', (string)$content);
    }
}
