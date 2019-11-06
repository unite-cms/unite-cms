<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Scalar;

use DateTime;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use UniteCMS\CoreBundle\Field\Types\DateType;

class DateResolver extends DateTimeResolver
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ScalarTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Date';
    }

    /**
     * {@inheritDoc}
     * @var DateTime $value
     */
    public function serialize($value)
    {
        return $value->format('Y-m-d');
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function parseValue($value)
    {
        return DateType::parseValue($value);
    }
}
