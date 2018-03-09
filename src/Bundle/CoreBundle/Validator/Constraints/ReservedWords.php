<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * Shows if the value is in the list of the reserved words.
 */
class ReservedWords extends Constraint
{
    public $message = 'The value is in the list of reserved words.';
    public $reserved = [];

    /**
     * @inheritdoc
     */
    public function getRequiredOptions()
    {
        return array('reserved');
    }
}