<?php

namespace UniteCMS\CoreBundle\View;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\View;

abstract class ViewType implements ViewTypeInterface
{
    const TYPE = "";
    const TEMPLATE = "";

    const SETTINGS = [];
    const REQUIRED_SETTINGS = [];

    /**
     * @var View $view
     */
    protected $view;

    static function getType(): string
    {
        return static::TYPE;
    }

    static function getTemplate(): string
    {
        return static::TEMPLATE;
    }

    function setEntity(View $view)
    {
        $this->view = $view;

        return $this;
    }

    function unsetEntity()
    {
        $this->view = null;
    }

    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array
    {
        return [];
    }

    function validateSettings(ViewSettings $settings): array
    {
        $violations = [];

        if(is_object($settings)) {
            $settings = get_object_vars($settings);
        }

        // Check that only allowed settings are present.
        foreach (array_keys($settings) as $setting) {
            if(!in_array($setting, static::SETTINGS)) {
                $violations[] = new ConstraintViolation(
                    'validation.additional_data',
                    'validation.additional_data',
                    [],
                    $settings,
                    $setting,
                    $settings
                );
            }
        }

        // Check that all required settings are present.
        foreach (static::REQUIRED_SETTINGS as $setting) {
            if(!isset($settings[$setting])) {
                $violations[] = new ConstraintViolation(
                    'validation.required',
                    'validation.required',
                    [],
                    $settings,
                    $setting,
                    $settings
                );
            }
        }

        return $violations;
    }
}
