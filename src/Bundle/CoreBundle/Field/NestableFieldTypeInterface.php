<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 15.02.18
 * Time: 11:09
 */

namespace UniteCMS\CoreBundle\Field;

use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;

/**
 * A field type that can have nested children.
 */
interface NestableFieldTypeInterface extends FieldTypeInterface
{
    /**
     * Returns a (virtual) entity fields from a given FieldableField.
     *
     * @param FieldableField $field
     * @return Fieldable
     */
    static function getNestableFieldable(FieldableField $field): Fieldable;
}
