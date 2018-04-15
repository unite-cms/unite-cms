<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class FieldType extends Constraint
{
    public $message = 'This type is not a registered field type.';
}
