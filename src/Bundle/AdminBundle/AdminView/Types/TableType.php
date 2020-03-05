<?php

namespace UniteCMS\AdminBundle\AdminView\Types;

use Doctrine\Common\Collections\ArrayCollection;
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
    public function createView(string $category, ?ContentType $contentType = null, ?FragmentDefinitionNode $definition = null, ?array $directive = null, array $nativeFragments = []): AdminView {
        $config = new ArrayCollection();
        $config->set('limit', empty($directive['settings']['limit']) ? 20 : $directive['settings']['limit']);
        $config->set('orderBy', empty($directive['settings']['orderBy']) ? [['field' => 'created', 'order' => 'DESC']] : $directive['settings']['orderBy']);
        $config->set('miniPager', empty($directive['settings']['miniPager']) ? false : $directive['settings']['miniPager']);
        $config->set('showTotal', empty($directive['settings']['showTotal']) ? false : $directive['settings']['showTotal']);
        $config->set('actions', [
            'create' => true,
            'toggle_delete' => true,
            'filter' => true,
            'update' => true,
            'delete' => true,
            'translate' => true,
            'revert' => true,
            'recover' => true,
            'permanent_delete' => true,
            'user_invite' => true,
        ]);

        if($directive) {
            if (!empty($directive['settings']['filter']['field']) || !empty($directive['settings']['filter']['AND']) || !empty($directive['settings']['filter']['OR'])) {
                $config->set('filter', $directive['settings']['filter']);
            }

            if (!empty($directive['settings']['actions'])) {
                $actions = $config->get('actions');
                foreach($directive['settings']['actions'] as $key => $value) {
                    $actions[$key] = (bool)$value;
                }
                $config->set('actions', $actions);
            }
        }

        return parent::createView($category, $contentType, $definition, $directive, $nativeFragments)->setConfig($config);
    }
}
