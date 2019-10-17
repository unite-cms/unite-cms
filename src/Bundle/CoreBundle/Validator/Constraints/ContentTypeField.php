<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContentTypeField extends Constraint
{
    public $invalid_type_message = 'Field Type "{{ field_type }}" was not found.';
}
