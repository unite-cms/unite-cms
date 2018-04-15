<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidPermissions extends Constraint
{
    public $message = 'Invalid permissions or roles where selected.';
    public $callbackAttributes = null;
    public $callbackRoles = null;

    /**
     * @inheritdoc
     */
    public function getRequiredOptions()
    {
        return array('callbackAttributes', 'callbackRoles');
    }
}
