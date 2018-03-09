<?php

namespace UnitedCMS\CoreBundle\Form;

use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\Field\FieldTypeInterface;

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