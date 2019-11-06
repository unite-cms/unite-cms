<?php


namespace UniteCMS\CoreBundle\Expression\FunctionProviders;

use DateTime;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class DateProvider implements ExpressionFunctionProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new ExpressionFunction('now', function () {
                return 'now()';
            }, function (array $variables) {
                return new DateTime('now');
            }),
        ];
    }
}
