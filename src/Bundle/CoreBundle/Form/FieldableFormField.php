<?php

namespace UniteCMS\CoreBundle\Form;

use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;

class FieldableFormField
{
    /**
     * @var FieldTypeInterface
     */
    private $fieldType;

    /**
     * @var FieldableField
     */
    private $fieldDefinition;

    public function __construct(FieldTypeInterface $fieldType, FieldableField $fieldDefinition)
    {
        $this->fieldType = $fieldType;
        $this->fieldDefinition = $fieldDefinition;
    }

    public function getFieldType() : FieldTypeInterface {
        return $this->fieldType;
    }

    public function getFieldDefinition() : FieldableField {
        return $this->fieldDefinition;
    }
}
