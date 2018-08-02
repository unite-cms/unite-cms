<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 12:06
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use UniteCMS\CoreBundle\Security\AccessExpressionChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;


class ValidWebhooksValidator extends ConstraintValidator
{
    /**
     * @var AccessExpressionChecker $accessExpressionChecker
     */
    private $accessExpressionChecker;

    public function __construct()
    {
        $this->accessExpressionChecker = new AccessExpressionChecker();
    }

    public function validate($value, Constraint $constraint)
    {
        $allowedAttributes = [
            'url',
            'action',
            'ssl_check',
            'secret_key'
        ];

        print_r($value);
        exit;

        #foreach ($value as $attribute => $expression) {
        #    if (!in_array($attribute, $allowedAttributes)) {
        #        $this->context->buildViolation($constraint->message)->addViolation();
        #    }

            /*if(!$this->accessExpressionChecker->validate($expression)) {
                $this->context->buildViolation($constraint->message)->addViolation();
                return;
            }*/
        #}
    }
}