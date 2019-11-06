<?php


namespace UniteCMS\CoreBundle\Expression\FunctionProviders;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class AuthenticationProvider implements ExpressionFunctionProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new ExpressionFunction('has_role', function ($role) {
                return sprintf('$user->hasRole(%s', $role);
            }, function (array $variables, $role) {
                return $variables['user']->hasRole($role);
            }),
        ];
    }
}
