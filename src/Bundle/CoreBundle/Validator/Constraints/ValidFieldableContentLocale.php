<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidFieldableContentLocale extends Constraint
{
    public $message = 'This locale is not supported by this content type';
}