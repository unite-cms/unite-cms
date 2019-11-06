<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Scalar;


use DateTime;
use GraphQL\Error\Error;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use UniteCMS\CoreBundle\Field\Types\DateTimeType;

class DateTimeResolver implements ScalarResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ScalarTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'DateTime';
    }

    /**
     * {@inheritDoc}
     * @var DateTime $value
     */
    public function serialize($value)
    {
        return $value->format('c');
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function parseValue($value)
    {
        return DateTimeType::parseValue($value);
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function parseLiteral($valueNode, array $variables = null)
    {
        if($valueNode instanceof IntValueNode) {
            return $this->parseValue((int)$valueNode->value);
        }

        else if($valueNode instanceof StringValueNode) {
            return $this->parseValue((string)$valueNode->value);
        }

        throw new Error("Date input must be a unix timestamp INT or a date string.", [$valueNode]);
    }
}
