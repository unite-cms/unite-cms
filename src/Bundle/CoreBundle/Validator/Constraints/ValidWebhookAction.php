<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidWebhookAction extends Constraint
{
    public $message = 'Invalid webhook expression found';
}