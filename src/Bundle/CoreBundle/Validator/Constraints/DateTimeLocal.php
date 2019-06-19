<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateTimeLocal extends Constraint
{
    public $format = 'Y-m-d\TH:i';
    public $message = 'This value is not a valid datetime ("yyyy-MM-ddThh:mm").';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
