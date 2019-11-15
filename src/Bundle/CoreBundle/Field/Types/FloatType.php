<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;

class FloatType extends AbstractFieldType
{
    const TYPE = 'float';
    const GRAPHQL_INPUT_TYPE = Type::FLOAT;
}
