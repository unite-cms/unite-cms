<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DefaultViewType extends Constraint
{
    public $message = 'The default view type is missing';
}
