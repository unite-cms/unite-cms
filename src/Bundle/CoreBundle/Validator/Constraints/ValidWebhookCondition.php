<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidWebhookCondition extends Constraint
{
    public $message = 'Invalid webhook expression found';
}