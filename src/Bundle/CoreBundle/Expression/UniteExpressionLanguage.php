<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:40
 */

namespace UniteCMS\CoreBundle\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * The unite expression language don't provide any functions that would expose sensitive information to the user.
 * Expressions can be defined at different places by a domain admin
 */
class UniteExpressionLanguage extends ExpressionLanguage
{
    /**
     * Don't register the constant function.
     */
    protected function registerFunctions()
    {}
}