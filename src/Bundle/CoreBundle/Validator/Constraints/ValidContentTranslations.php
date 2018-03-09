<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidContentTranslations extends Constraint
{
    public $uniqueLocaleMessage = 'There are two ore more translations in the same language.';
    public $nestedTranslationMessage = 'Translations cannot have other content as translation.';
}