<?php

namespace UniteCMS\CoreBundle\View\Types;

use UniteCMS\CoreBundle\Entity\View;

/**
 * @deprecated 1.0, Please use the sortable option on the table view.
 */
class SortableViewType extends TableViewType
{
    const TYPE = "sortable";

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $params = parent::getTemplateRenderParameters($view, $selectMode);
        $params['sort']['sortable'] = true;
        $params['sort']['asc'] = true;
        return $params;
    }
}
