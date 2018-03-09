<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 16.02.18
 * Time: 10:36
 */

namespace UnitedCMS\StorageBundle\Field\Types;


use UnitedCMS\CoreBundle\Entity\FieldableField;

class ImageFieldType extends FileFieldType
{
    const TYPE                      = "image";
    const SETTINGS                  = ['bucket', 'thumbnail_url'];
    const REQUIRED_SETTINGS         = ['bucket'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array {
        $options = parent::getFormOptions($field);
        $options['attr']['thumbnail-url'] = $field->getSettings()->file_types ?? '{endpoint}/{id}/{name}';
        $options['attr']['file-types'] = 'png,gif,jpeg,jpg';
        return $options;
    }
}