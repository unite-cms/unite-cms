<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.06.18
 * Time: 08:53
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;

class ValidIdentifierValidator extends RegexValidator
{
    private $pattern;

    public function __construct($identifier_pattern)
    {
        $this->pattern = '/'.$identifier_pattern.'/';
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        // Use the regular regex validator but with a fixed pattern.
        $constraint->pattern = $this->pattern;
        parent::validate($value, $constraint);
    }
}