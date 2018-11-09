<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 16.02.18
 * Time: 10:36
 */

namespace UniteCMS\StorageBundle\Field\Types;


use UniteCMS\CoreBundle\Entity\FieldableField;

class ImageFieldType extends FileFieldType
{
    const TYPE                      = "image";
    const SETTINGS                  = ['not_empty', 'description', 'bucket', 'thumbnail_url', 'file_types'];
    const REQUIRED_SETTINGS         = ['bucket'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array {
        $options = parent::getFormOptions($field);
        $options['attr']['thumbnail-url'] = $field->getSettings()->thumbnail_url ?? '{endpoint}/{id}/{name}';
        $options['attr']['file-types'] = $field->getSettings()->file_types ?? 'png,gif,jpeg,jpg';
        return $options;
    }
}
