<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidContentTranslationOf extends Constraint
{
    public $uniqueLocaleMessage = 'There are two ore more translations in the same language.';
}
