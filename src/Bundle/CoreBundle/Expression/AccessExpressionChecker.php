<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:18
 */

namespace UniteCMS\CoreBundle\Expression;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\FieldableContent;

class AccessExpressionChecker
{

    /**
     * Returns the expression evaluation result, makes the domain member and fieldable content available in the expression.
     *
     * @param string $expression
     * @param DomainMember $domainMember
     * @param FieldableContent|null $fieldableContent
     * @return bool
     */
    public function evaluate(string $expression, DomainMember $domainMember, FieldableContent $fieldableContent = null) : bool {
        $expressionLanguage = new UniteExpressionLanguage();

        $variables = ['member' => [
            'data' => json_decode(json_encode($domainMember->getData())),
        ]];

        if($domainMember->getDomainMemberType()) {
            $variables['member']['type'] = $domainMember->getDomainMemberType()->getIdentifier();
        }

        if($domainMember->getAccessor()) {
            $variables['member']['accessor'] = (object)[
                'name' => (string)$domainMember->getAccessor(),
                'id' => (string)$domainMember->getAccessor()->getId(),
                'type' => ($domainMember->getAccessor() instanceof ApiKey) ? 'api_key' : 'user',
            ];
        }

        $variables['member'] = (object)$variables['member'];

        if($fieldableContent) {
            $variables['content'] = (object)[
                'locale' => $fieldableContent->getLocale(),
                'data' => json_decode(json_encode($fieldableContent->getData())),
            ];
        }

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
        $variables = ['member', 'content'];

        try {
            $expressionLanguage->parse($expression, $variables);
        }

        catch (SyntaxError $error) {
            return false;
        }

        return true;
    }
}