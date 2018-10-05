<?php

namespace UniteCMS\CoreBundle\View\Types;

use UniteCMS\CoreBundle\Entity\View;

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

        foreach($params['columns'] as $field => $column) {
            if($field === $params['sort']['field']) {
                unset($params['columns'][$field]);
            }
        }

        $params['columns'] = [$params['sort']['field'] => ''] + $params['columns'];

        return $params;
    }
}
