<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidValidations extends Constraint
{
    public $message = 'Invalid validation definition found.';
}
