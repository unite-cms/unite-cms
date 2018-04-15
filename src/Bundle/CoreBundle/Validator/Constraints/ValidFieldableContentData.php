<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidFieldableContentData extends Constraint
{
    public $additionalDataMessage = "The content unit contains invalid additional data.";
}
