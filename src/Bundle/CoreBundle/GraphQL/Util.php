<?php


namespace UniteCMS\CoreBundle\GraphQL;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Util
{

    /**
     * @param $node
     * @param string $name
     * @return array|null
     */
    static function directiveArgs($node, string $name) : ?array {

        if(!is_object($node) || !property_exists($node, 'directives')) {
            return null;
        }

        foreach($node->directives as $directive) {
            if($directive->name->value === $name) {
                $args = [];
                foreach($directive->arguments as $argument) {
                    $args[$argument->name->value] = $argument->value->value;
                }
                return $args;
            }
        }

        return null;
    }

    /**
     * Returns true, this the given node is hidden.
     *
     * @param $node
     * @param AuthorizationCheckerInterface $authorizationChecker
     *
     * @return bool
     */
    static function isHidden($node, AuthorizationCheckerInterface $authorizationChecker) : bool {
        if($args = self::directiveArgs($node, 'hide')) {
            return $authorizationChecker->isGranted(new Expression($args['if']));
        }

        return false;
    }
}
