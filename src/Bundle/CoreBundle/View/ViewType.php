<?php

namespace UniteCMS\CoreBundle\View;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(ViewSettings $settings, ExecutionContextInterface $context)
    {
        if (is_object($settings)) {
            $settings = get_object_vars($settings);
        }

        // Check that only allowed settings are present.
        foreach (array_keys($settings) as $setting) {
            if (!in_array($setting, static::SETTINGS)) {
                $context->buildViolation('additional_data')->atPath($setting)->addViolation();
            }
        }

        // Check that all required settings are present.
        foreach (static::REQUIRED_SETTINGS as $setting) {
            if (!isset($settings[$setting])) {
                $context->buildViolation('required')->atPath($setting)->addViolation();
            }
        }
    }
}
