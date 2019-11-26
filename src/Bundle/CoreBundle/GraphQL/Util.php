<?php


namespace UniteCMS\CoreBundle\GraphQL;

use GraphQL\Language\AST\ListValueNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\ValueNode;
use Symfony\Component\ExpressionLanguage\Expression;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;

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

        else if ($node instanceof ObjectValueNode) {
            $values = [];
            foreach($node->fields as $field) {
                $values[$field->name->value] = static::getNodeValue($field->value);
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

        $suffixParts = array_filter(preg_split('/(?=[A-Z])/',$suffix));

        foreach($node->directives as $directive) {

            $directiveNameParts = array_filter(preg_split('/(?=[A-Z])/',$directive->name->value));

            if(count($directiveNameParts) > count($suffixParts)) {

                $foundSuffix = '';
                for($i = 0; $i < count($suffixParts); $i++) {
                    $foundSuffix = array_pop($directiveNameParts) . $foundSuffix;
                }

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
     * @param SaveExpressionLanguage $expressionLanguage
     *
     * @return bool
     */
    static function isHidden($node, SaveExpressionLanguage $expressionLanguage) : bool {
        foreach(self::getDirectives($node) as $directive) {
            if($directive['name'] === 'hide') {
                return (bool)$expressionLanguage->evaluate(new Expression($directive['args']['if']));
            }
        }

        return false;
    }
}
