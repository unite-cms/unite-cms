<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;

class ValidPermissionsValidator extends ConstraintValidator
{

    /**
     * @var UniteExpressionChecker $accessExpressionChecker
     */
    private $accessExpressionChecker;

    public function __construct()
    {
        $this->accessExpressionChecker = new UniteExpressionChecker();
        $this->accessExpressionChecker
            ->registerFieldableContent(null)
            ->registerDomainMember(null);
    }

    public function validate($value, Constraint $constraint)
    {

        $allowedAttributes = [];

        // Get all allowed attributes.
        if ($constraint->callbackAttributes) {
            if (!is_callable($allowedAttributes = array($this->context->getObject(), $constraint->callbackAttributes))
                && !is_callable(
                    $allowedAttributes = array($this->context->getClassName(), $constraint->callbackAttributes)
                )
                && !is_callable($allowedAttributes = $constraint->callbackAttributes)
            ) {
                throw new ConstraintDefinitionException(
                    'The ValidPermission constraint expects a valid allowedAttributesCallback'
                );
            }
            $allowedAttributes = call_user_func($allowedAttributes);
        }

        foreach ($value as $attribute => $expression) {
            if (!in_array($attribute, $allowedAttributes)) {
                $this->context->buildViolation($constraint->message)->addViolation();
                return;
            }

            if(!$this->accessExpressionChecker->validate($expression)) {
                $this->context->buildViolation($constraint->message)->addViolation();
                return;
            }
        }
    }
}
