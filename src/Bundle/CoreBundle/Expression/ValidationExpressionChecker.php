<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:18
 */

namespace UniteCMS\CoreBundle\Expression;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use UniteCMS\CoreBundle\Entity\FieldableContent;

class ValidationExpressionChecker
{

    /**
     * Returns the expression evaluation result, makes fieldable content available in the expression.
     *
     * @param string $expression
     * @param FieldableContent|null $fieldableContent
     * @return bool
     */
    public function evaluate(string $expression, FieldableContent $fieldableContent) : bool {
        $expressionLanguage = new UniteExpressionLanguage();


        $variables = [
            'locale' => $fieldableContent->getLocale(),
            'data' => json_decode(json_encode($fieldableContent->getData())),
        ];

        try {
            return (bool) $expressionLanguage->evaluate($expression, $variables);
        }

        // Silently cache all exceptions. The expression can be defined by the user and we don't want to show him_her an error page.
        catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Returns true, if the expression is valid (syntax and allowed variables).
     *
     * @param string $expression
     * @return bool
     */
    public function validate(string $expression) : bool {
        $expressionLanguage = new UniteExpressionLanguage();
        $variables = ['locale', 'data'];

        try {
            $expressionLanguage->parse($expression, $variables);
        }

        catch (SyntaxError $error) {
            return false;
        }

        return true;
    }
}