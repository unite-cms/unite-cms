<?php

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\ContentType\ContentTypeField;

class ChoiceType extends AbstractFieldType
{
    const TYPE = 'choice';

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return $field->getReturnType();
    }
}
