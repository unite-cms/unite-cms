<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 13:01
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Form\LinkType;
use UniteCMS\CoreBundle\Field\FieldType;

class LinkFieldType extends FieldType
{
    const TYPE = "link";
    const FORM_TYPE = LinkType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['allow_title', 'allow_target'];

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {

        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        if (!empty($settings->allow_title) && !is_bool($settings->allow_title)) {
            $context->buildViolation('noboolean_value')->atPath('allow_title')->addViolation();
        }

        if (!empty($settings->allow_target) && !is_bool($settings->allow_target)) {
            $context->buildViolation('noboolean_value')->atPath('allow_target')->addViolation();
        }

    }

}