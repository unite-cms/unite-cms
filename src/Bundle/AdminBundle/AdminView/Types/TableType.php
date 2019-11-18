<?php

namespace UniteCMS\AdminBundle\AdminView\Types;

use GraphQL\Language\AST\FragmentDefinitionNode;
use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\CoreBundle\ContentType\ContentType;

class TableType extends AbstractAdminViewType
{
    const TYPE = 'table';
    const RETURN_TYPE = 'TableAdminView';

    /**
     * {@inheritDoc}
     */
    public function createView(FragmentDefinitionNode $definition, array $directive, string $category, ContentType $contentType) : AdminView {
        $config = [
            'limit' => $directive['settings']['limit'] ?? 20,
        ];

        if(!empty($directive['settings']['filter']['field']) || !empty($directive['settings']['filter']['AND']) || !empty($directive['settings']['filter']['OR'])) {
            $config['filter'] = $directive['settings']['filter'];
        }

        if(!empty($directive['settings']['orderBy'])) {
            $config['orderBy'] = $directive['settings']['orderBy'];
        }

        return parent::createView($definition, $directive, $category, $contentType)->setConfig($config);
    }
}
