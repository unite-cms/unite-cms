<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:40
 */

namespace UniteCMS\CoreBundle\Expression;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * The unite expression language don't provide any functions that would expose sensitive information to the user.
 * Expressions can be defined at different places by a domain admin
 */
class UniteExpressionLanguage extends ExpressionLanguage
{
    public function __construct(CacheItemPoolInterface $cache = null, $providers = array())
    {
        $providers[] = new UniteExpressionLanguageProvider();
        parent::__construct($cache, $providers);
    }

    /**
     * Don't register the constant function.
     */
    protected function registerFunctions() {}
}
