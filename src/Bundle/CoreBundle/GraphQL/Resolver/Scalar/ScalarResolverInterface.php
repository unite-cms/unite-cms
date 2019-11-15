<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Scalar;

use GraphQL\Language\AST\ScalarTypeDefinitionNode;

/**
 * Interface ScalarResolverInterface
 *
 * @package App\GraphQL\Resolver
 */
interface ScalarResolverInterface
{
    /**
     * Return true, if this resolver supports the given type.
     *
     * @param string $typeName
     * @param ScalarTypeDefinitionNode $typeDefinitionNode
     *
     * @return bool
     */
    public function supports(string $typeName, ScalarTypeDefinitionNode $typeDefinitionNode) : bool;

    /**
     * Serialize a scalar value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function serialize($value);

    /**
     * Parse a scalar value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function parseValue($value);

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @param $valueNode
     * @param array|null $variables
     *
     * @return mixed
     */
    public function parseLiteral($valueNode, array $variables = null);
}
