<?php


namespace UniteCMS\CoreBundle\GraphQL;

use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\ValueNode;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Util
{
    static function getNodeValue(ValueNode $node) {
        if($node instanceof ListValueNode) {
            $values = [];
            foreach($node->values as $listItem) {
                $values[] = static::getNodeValue($listItem);
            }
            return $values;
        }
        return $node->value;
    }

    /**
     * Get all directives of a AST node.
     *
     * @param $node
     * @return array|null
     */
    static function getDirectives($node) : array {

        if(!is_object($node) || !property_exists($node, 'directives')) {
            return [];
        }

        $directives = [];

        foreach($node->directives as $directive) {
            $args = [];
            foreach($directive->arguments as $argument) {
                $args[$argument->name->value] = static::getNodeValue($argument->value);
            }
            $directives[] = [
                'name' => $directive->name->value,
                'args' => $args,
            ];
        }

        return $directives;
    }

    /**
     * Find a typed directive with a special suffix.
     *
     * @param $node
     * @param string $suffix
     *
     * @return array|null
     */
    static function typedDirectiveArgs($node, string $suffix) : ?array {

        if(!is_object($node) || !property_exists($node, 'directives')) {
            return null;
        }

        foreach($node->directives as $directive) {

            $directiveNameParts = preg_split('/(?=[A-Z])/',$directive->name->value);

            if(count($directiveNameParts) >= 2) {
                $foundSuffix = array_pop($directiveNameParts);
                $type = substr($directive->name->value, 0, -strlen($foundSuffix));

                if($foundSuffix === $suffix) {
                    $args = [
                        'type' => $type,
                        'settings' => []
                    ];

                    foreach($directive->arguments as $argument) {
                        $args['settings'][$argument->name->value] = static::getNodeValue($argument->value);
                    }

                    return $args;
                }
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
        foreach(self::getDirectives($node) as $directive) {
            if($directive['name'] === 'hide') {
                return $authorizationChecker->isGranted(new Expression($directive['args']['if']));
            }
        }

        return false;
    }
}
