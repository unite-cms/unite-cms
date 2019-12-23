<?php


namespace UniteCMS\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_ACCESS_ADMIN_VIEWS_EXPRESSION = 'not user.isAnonymous()';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('unite_cms_admin');
        $treeBuilder->getRootNode()
        ->children()
            ->scalarNode('access_admin_views_expression')
                ->cannotBeEmpty()
                ->defaultValue(static::DEFAULT_ACCESS_ADMIN_VIEWS_EXPRESSION)
            ->end()
            ->scalarNode('access_logs_expression')->defaultNull()->end()
            ->scalarNode('access_schema_files')->defaultNull()->end()
            ->scalarNode('access_query_explorer')->defaultNull()->end()
        ->end();
        return $treeBuilder;
    }
}
