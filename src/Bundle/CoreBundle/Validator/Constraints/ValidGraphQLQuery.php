<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidGraphQLQuery extends Constraint
{
    public $message = 'Invalid graphql query. Query must start with query { ... }.';
}
