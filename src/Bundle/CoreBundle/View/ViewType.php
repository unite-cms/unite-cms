<?php

namespace UniteCMS\CoreBundle\View;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\View;

abstract class ViewType implements ViewTypeInterface
{
    const TYPE = "";
    const TEMPLATE = "";

    static function getType(): string
    {
        return static::TYPE;
    }

    static function getTemplate(): string
    {
        return static::TEMPLATE;
    }

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(ViewSettings $settings, ExecutionContextInterface $context) { }
}
