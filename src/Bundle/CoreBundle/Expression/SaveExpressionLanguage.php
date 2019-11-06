<?php


namespace UniteCMS\CoreBundle\Expression;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Expression\FunctionProviders\AuthenticationProvider;
use UniteCMS\CoreBundle\Expression\Variables\ProxyContent;
use UniteCMS\CoreBundle\Expression\Variables\ProxyUser;
use UniteCMS\CoreBundle\Security\User\UserInterface;

/**
 * This expression language doesn't provide any functions that would expose
 * sensitive information to the user.
 */
class SaveExpressionLanguage extends BaseExpressionLanguage
{

    /**
     * @var Security $security
     */
    protected $security;

    public function __construct(Security $security, CacheItemPoolInterface $cache = null)
    {
        $this->security = $security;
        parent::__construct($cache, [
            new AuthenticationProvider(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function evaluate($expression, $values = [])
    {
        // Transform given values to proxy values to avoid access to sensitive information.
        foreach($values as $key => $value) {
            if(is_object($value)) {
                if($value instanceof UserInterface) {
                    $values[$key] = new ProxyUser($value);
                }

                else if($value instanceof ContentInterface) {
                    $values[$key] = new ProxyContent($value);
                }
            }
        }

        // Provide default values.
        if(empty($values['user'])) {
            $values['user'] = new ProxyUser($this->security->getUser());
        }

        if(empty($values['content'])) {
            $values['content'] = new ProxyContent();
        }

        return parent::evaluate($expression, $values);
    }

    /**
     * {@inheritDoc}
     */
    protected function registerFunctions() {
        // DO NOT CALL PARENT! THIS WILL REMOVE const() function.
    }
}
