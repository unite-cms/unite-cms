<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;

class IntegerType extends AbstractFieldType
{
    const TYPE = 'integer';
    const GRAPHQL_INPUT_TYPE = Type::INT;
}
