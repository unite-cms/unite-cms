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

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $setting = parent::getTemplateRenderParameters($view, $selectMode);

        if($setting['children_field']) {
            $setting['parent_field'] = $view->getContentType()->getFields()->get($setting['children_field'])->getSettings()->reference_field;
        }

        return $setting;
    }
}
