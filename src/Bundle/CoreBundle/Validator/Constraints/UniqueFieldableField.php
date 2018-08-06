<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueFieldableField extends Constraint
{
    public $message = 'The field identifier is already taken';
    public $getter = 'getIdentifier';

    /**
     * Returns whether the constraint can be put onto classes, properties or
     * both.
     *
     * This method should return one or more of the constants
     * Constraint::CLASS_CONSTRAINT and Constraint::PROPERTY_CONSTRAINT.
     *
     * @return string|array One or more constant values
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
