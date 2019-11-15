<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Scalar;


use GraphQL\Language\AST\ScalarTypeDefinitionNode;

class NullResolver implements ScalarResolverInterface
{

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ScalarTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'NULL';
    }

    /**
     * {@inheritDoc}
     */
    public function serialize($value)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function parseValue($value)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        return '';
    }
}
