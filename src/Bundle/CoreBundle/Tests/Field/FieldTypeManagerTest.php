<?php

namespace UniteCMS\CoreBundle\Tests\View;

use PHPUnit\Framework\TestCase;
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
                return 'custom_prefix_' . parent::getTitle($field);
            }
        };

        $manager = new FieldTypeManager();
        $manager->registerFieldType($fieldType);


        // Check that the fieldType was registered.
        $this->assertEquals($fieldType, $manager->getFieldType('test_register_field_test_type'));
    }
}
