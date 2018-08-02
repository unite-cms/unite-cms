<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidWebhooks extends Constraint
{
    public $message = 'Unsupported webhooks or invalid expressions found.';
    #public $callbackAttributes = null;

    /**
     * @inheritdoc
     */
    /*public function getRequiredOptions()
    {
        return array('callbackAttributes');
    }*/
}