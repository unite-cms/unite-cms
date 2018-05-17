<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:40
 */

namespace UniteCMS\CoreBundle\Security;


use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * The plain expression language don't provide any functions and can only decide based on variables and basic syntax
 * elements.
 */
class PlainExpressionLanguage extends ExpressionLanguage
{
    /**
     * Don't register the constant function.
     */
    protected function registerFunctions()
    {}
}