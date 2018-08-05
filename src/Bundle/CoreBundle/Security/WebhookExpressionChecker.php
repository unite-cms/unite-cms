<?php

namespace UniteCMS\CoreBundle\Security;

use Symfony\Component\ExpressionLanguage\SyntaxError;

class WebhookExpressionChecker
{

   /**
     * Returns the expression evaluation result, makes the possible doctrine events available
     *
     * @param string $expression
     * @param string $eventName
     * @return bool
     */
    public function evaluate(string $expression, string $eventName) : bool {
        $expressionLanguage = new PlainExpressionLanguage();
        $variables['event'] = $eventName;

        try {
            return (bool) $expressionLanguage->evaluate($expression, $variables);
        }

        // Silently cache all exceptions. The expression can be defined by the user and we don't want to show him_her an error page.
        catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Returns true, of the expression is valid (syntax and allowed variables).
     *
     * @param string $expression
     * @return bool
     */
    public function validate(string $expression) : bool {
        $expressionLanguage = new PlainExpressionLanguage();
        $variables = ['event'];

        try {
            $expressionLanguage->parse($expression, $variables);
        }

        catch (SyntaxError $error) {
            return false;
        }

        return true;
    }
}