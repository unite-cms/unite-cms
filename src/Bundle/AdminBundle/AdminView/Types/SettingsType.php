<?php

namespace UniteCMS\AdminBundle\AdminView\Types;

use GraphQL\Language\AST\FragmentDefinitionNode;
use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\CoreBundle\ContentType\ContentType;

class SettingsType extends AbstractAdminViewType
{
    const TYPE = 'settings';
    const RETURN_TYPE = 'SettingsAdminView';

    /**
     * {@inheritDoc}
     */
    public function createView(string $category, ?ContentType $contentType = null, ?FragmentDefinitionNode $definition = null, ?array $directive = null) : AdminView {
        $view = parent::createView($category, $contentType, $definition, $directive);
        $view->setTitlePattern($view->getName());
        return $view;
    }
}
