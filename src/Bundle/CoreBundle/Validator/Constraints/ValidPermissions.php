<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidPermissions extends Constraint
{
    public $message = 'Unsupported permissions or invalid expressions found.';
    public $callbackAttributes = null;

    /**
     * @inheritdoc
     */
    public function getRequiredOptions()
    {
        return array('callbackAttributes');
    }
}
