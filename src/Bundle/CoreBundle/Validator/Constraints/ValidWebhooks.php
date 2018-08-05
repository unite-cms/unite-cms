<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidWebhooks extends Constraint
{
    public $message = 'Unsupported webhooks or invalid expressions found.';
    public $uniqueUrlMessage = 'There are two ore more webhooks with the same Url.';
    public $invalidCheckSSLMessage = 'The Check SSL Flag should be a boolean value';
    public $invalidSecretKeyMessage = 'The Secret key should be longer than 8 characters';
    public $invalidExpressionMessage = 'The given fire expression is not valid';
    public $invalidAttributeMessage = 'One given attribute is not valid';
    public $requiredAttributeMissingMessage = 'Not all required attributes are given';
}