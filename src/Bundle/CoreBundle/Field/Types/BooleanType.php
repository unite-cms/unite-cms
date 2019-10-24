<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;

class BooleanType extends AbstractFieldType
{
    const TYPE = 'boolean';
    const GRAPHQL_INPUT_TYPE = Type::BOOLEAN;
}
