<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidWebhooks extends Constraint
{
    public $message = 'Invalid webhook expression found';
}