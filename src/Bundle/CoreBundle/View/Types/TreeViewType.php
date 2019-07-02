<?php

namespace UniteCMS\CoreBundle\View\Types;

use UniteCMS\CoreBundle\Entity\View;

class TreeViewType extends TableViewType
{
    const TYPE = "tree";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Tree/index.html.twig";

    protected function addRecursiveChildrenFields($fields, $children_field, $sort, $level = 3) {
        return array_merge($fields, [
            $children_field => [
                'type' => 'tree_view_children',
                'settings' => [
                    'fields' => $level > 0 ? $this->addRecursiveChildrenFields($fields, $children_field, $sort, $level -1) : $fields,
                    'sort' => $sort,
                ],
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $settings = parent::getTemplateRenderParameters($view, $selectMode);

        // Pass parent_field settings to the view.
        $children_field_settings = $view->getContentType()->getFields()->get($settings['children_field'])->getSettings();
        $settings['parent_field'] = $children_field_settings->reference_field;
        $settings['content_type'] = $children_field_settings->content_type;
        $settings['domain'] = $children_field_settings->domain;

        // If settings have a content filter already set, remove it first.
        if(!empty($settings['filter']['field']) && $settings['filter']['field'] === $settings['parent_field'].'.content') {
            $settings['filter'] = [];
        }

        // Pass root level filter to the view.
        $rootLevelFilter = ['field' => $settings['parent_field'].'.content', 'operator' => 'IS NULL'];
        $settings['filter'] = empty($settings['filter']) ? $rootLevelFilter : ['AND' => [$rootLevelFilter, $settings['filter']]];

        // Add a custom reference_of field to the view
        $settings['fields'] = $this->addRecursiveChildrenFields($settings['fields'], $settings['children_field'], $settings['sort']);

        return $settings;
    }
}
