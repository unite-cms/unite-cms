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
use UniteCMS\CoreBundle\Security\PlainExpressionLanguage;

class ContentExpressionChecker
{
    /**
     * Returns the expression evaluation result, makes the domain member and fieldable content available in the expression.
     *
     * @param string $expression
     * @param FieldableContent|null $fieldableContent
     * @param array $contentData
     * @return string
     */
    public function evaluate(string $expression, FieldableContent $fieldableContent, array $contentData = []) : string {
        $expressionLanguage = new PlainExpressionLanguage();

        $variables['content'] = (object)[
            'locale' => $fieldableContent->getLocale(),
            'data' => json_decode(json_encode(empty($contentData) ?$fieldableContent->getData() : $contentData)),
        ];

        try {
            return (string) $expressionLanguage->evaluate($expression, $variables);
        }

        // Silently cache all exceptions. The expression can be defined by the user and we don't want to show him_her an error page.
        catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * Returns true, if the expression is valid (syntax and allowed variables).
     *
     * @param string $expression
     * @return bool
     */
    public function validate(string $expression) : bool {
        $expressionLanguage = new PlainExpressionLanguage();
        $variables = ['content'];

        try {
            $expressionLanguage->parse($expression, $variables);
        }

        catch (SyntaxError $error) {
            return false;
        }

        return true;
    }
}