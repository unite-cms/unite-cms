<?php


namespace UniteCMS\CoreBundle\Expression;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage as BaseExpressionLanguage;

/**
 * This expression language doesn't provide any functions that would expose
 * sensitive information to the user.
 */
class AuthorizationExpressionLanguage extends BaseExpressionLanguage
{
    public function __construct(CacheItemPoolInterface $cache = null, $providers = array())
    {
        parent::__construct($cache, $providers);
    }

    /**
     * {@inheritDoc}
     */
    protected function registerFunctions() {
        // DO NOT DELETE OVERRIDE. THIS WILL REMOVE const() function.
    }
}
