<?php

namespace UniteCMS\CoreBundle\Tests\View;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class FieldTypeManagerTest extends TestCase
{

    public function testRegisterFields()
    {

        $fieldType = new class extends FieldType
        {
            const TYPE = "test_register_field_test_type";

            public function getTitle(FieldableField $field): string
            {
                return 'custom_prefix_'.parent::getTitle($field);
            }
        };

        $manager = new FieldTypeManager();
        $manager->registerFieldType($fieldType);


        // Check that the fieldType was registered.
        $this->assertEquals($fieldType, $manager->getFieldType('test_register_field_test_type'));
    }

    public function testValidateFieldDataMethod() {
        $manager = new FieldTypeManager();
        $manager->registerFieldType(new class extends FieldType {
            const TYPE = 'test_t1';
            public function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData)
            {
                if(array_key_exists($field->getIdentifier(), $content->getData())) {
                    $data[$field->getIdentifier()] .= '_' . $content->getData()[$field->getIdentifier()];
                } else {
                    $data[$field->getIdentifier()] = 'NEW';
                }

            }
        });
        $manager->registerFieldType(new class extends FieldType {
            const TYPE = 'test_t2';
        });

        $content = new Content();
        $contentType = new ContentType();
        $content->setContentType($contentType);

        $ctField1 = new ContentTypeField();
        $ctField1->setIdentifier('f1')->setType('test_t1');

        $ctField2 = new ContentTypeField();
        $ctField2->setIdentifier('f2')->setType('test_t1');

        $ctField3 = new ContentTypeField();
        $ctField3->setIdentifier('f3')->setType('test_t2');


        $contentType
            ->addField($ctField1)
            ->addField($ctField2)
            ->addField($ctField3);


        $data = [];

        $manager->alterFieldData($ctField1, $data, $content, $data);
        $manager->alterFieldData($ctField2, $data, $content, $data);
        $manager->alterFieldData($ctField3, $data, $content, $data);

        $this->assertEquals([
            'f1' => 'NEW',
            'f2' => 'NEW',
        ], $data);

        $data['f3'] = 'FOO';
        $content->setData($data);

        $manager->alterFieldData($ctField1, $data, $content, $data);
        $manager->alterFieldData($ctField2, $data, $content, $data);
        $manager->alterFieldData($ctField3, $data, $content, $data);

        $this->assertEquals([
            'f1' => 'NEW_NEW',
            'f2' => 'NEW_NEW',
            'f3' => 'FOO',
        ], $data);
    }
}
