<?php

namespace UniteCMS\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('unite_cms_core');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('domain_config_dir')
                    ->defaultValue('%kernel.project_dir%/config/unite/')
                    ->info('The location to store domain configurations. Content in this directory can get deleted, when you create or update an organization or domain!')
                ->end()
                ->integerNode('maximum_nesting_level')
                    ->defaultValue(16)
                    ->info('Set the maximum nesting level of GraphQL API queries. A high value can easily lead to performance issues!')
                ->end()
                ->integerNode('maximum_query_limit')
                    ->defaultValue(100)
                    ->info('Set the maximum query limit of GraphQL API queries. A high value can easily lead to performance issues!')
                ->end()
                ->arrayNode('domain_config_parameters')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->info('Allows to define an array of global parameters that can be used inside all domain configurations. (example configuration: foo: "Baa", usage in domain config: "%foo%".')
                ->end()
            ->end();
        return $treeBuilder;
    }
}
