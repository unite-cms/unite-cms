<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ViewType extends Constraint
{
    public $message = 'This type is not a registered view type.';
}
