<?php

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\View;

class TreeViewType extends TableViewType
{
    const TYPE = "tree";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Tree/index.html.twig";

    protected function createConfig(ContentType $contentType) : ConfigurationInterface {
        return new TreeViewConfiguration($contentType, $this->fieldTypeManager);
    }

    protected function addRecursiveChildrenFields($fields, $children_field, $sort, $level = 6) {
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

        // Pass parent_field identifier to the view.
        $settings['parent_field'] = $view->getContentType()->getFields()->get($settings['children_field'])->getSettings()->reference_field;

        // Pass root level filter to the view.
        $rootLevelFilter = ['field' => $settings['parent_field'].'.content', 'operator' => 'IS NULL'];
        $settings['filter'] = empty($settings['filter']) ? $rootLevelFilter : ['AND' => [$rootLevelFilter, $settings['filter']]];

        // Add a custom reference_of field to the view
        $settings['fields'] = $this->addRecursiveChildrenFields($settings['fields'], $settings['children_field'], $settings['sort']);

        return $settings;
    }
}
