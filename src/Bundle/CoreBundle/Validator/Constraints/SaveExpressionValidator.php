<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\ExpressionValidator;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;

class SaveExpressionValidator extends ExpressionValidator
{
    public function __construct($propertyAccessor = null, SaveExpressionLanguage $expressionLanguage) {
        parent::__construct($propertyAccessor, $expressionLanguage);
    }
}
