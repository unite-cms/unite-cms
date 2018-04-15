<?php

namespace UniteCMS\CoreBundle\View;

use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\View;

interface ViewTypeInterface
{

    const SELECT_MODE_NONE = 'SELECT_MODE_NONE';
    const SELECT_MODE_SINGLE = 'SELECT_MODE_SINGLE';

    // TODO: This is not implemented yet.
    //const SELECT_MODE_MULTIPLE = 'SELECT_MODE_MULTIPLE';

    static function getType(): string;

    static function getTemplate(): string;

    function setEntity(View $view);

    function unsetEntity();

    /**
     * @param string $selectMode, the select mode. Default's to none. But can also be single or multiple.
     *
     * @return array
     */
    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array;

    /**
     * @param ViewSettings $settings
     *
     * @return ConstraintViolation[]
     */
    function validateSettings(ViewSettings $settings) : array;
}
