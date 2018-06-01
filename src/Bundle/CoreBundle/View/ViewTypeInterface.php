<?php

namespace UniteCMS\CoreBundle\View;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\View;

interface ViewTypeInterface
{

    const SELECT_MODE_NONE = 'SELECT_MODE_NONE';
    const SELECT_MODE_SINGLE = 'SELECT_MODE_SINGLE';

    // TODO: This is not implemented yet.
    //const SELECT_MODE_MULTIPLE = 'SELECT_MODE_MULTIPLE';

    static function getType(): string;
    static function getTemplate(): string;

    /**
     * @param View $view
     * @param string $selectMode , the select mode. Default's to none. But can also be single or multiple.
     *
     * @return array
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array;

    /**
     * @param ViewSettings $settings
     * @param ExecutionContextInterface $context
     *
     * @return ConstraintViolation[]
     */
    function validateSettings(ViewSettings $settings, ExecutionContextInterface $context);
}
