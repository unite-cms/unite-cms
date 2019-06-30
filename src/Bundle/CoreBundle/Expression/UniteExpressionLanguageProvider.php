<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-01-07
 * Time: 10:52
 */

namespace UniteCMS\CoreBundle\Expression;

use Cocur\Slugify\Slugify;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class UniteExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var Slugify $slugify
     */
    private $slugify;

    public function __construct()
    {
        $this->slugify = new Slugify();
    }

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [

            // Generates a slug from a given string using Cocur\Slugify.
            new ExpressionFunction('slug', function ($str) {}, function ($arguments, $str) {
                if (!is_string($str)) {
                    return $str;
                }
                return $this->slugify->slugify($str);
            }),
        ];
    }
}
