<?php

namespace UniteCMS\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class UniteCMSCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set domain config dir as first argument to domain config manager.
        $container->getDefinition('unite.cms.domain_config_manager')->setArgument(0, $config['domain_config_dir']);

        // Set maximum nesting level as first argument to schema type manager and maximum nesting type object type.
        $container->getDefinition('unite.cms.graphql.schema_type_manager')->setArgument(0, $config['maximum_nesting_level']);
        $container->getDefinition('UniteCMS\CoreBundle\SchemaType\Types\MaximumNestingLevelType')->setArgument(0, $config['maximum_nesting_level']);

        // Set maximum query limit as fifth argument to query type and maximum reference of field type.
        $container->getDefinition('UniteCMS\CoreBundle\SchemaType\Types\QueryType')->setArgument(5, $config['maximum_query_limit']);
        $container->getDefinition('UniteCMS\CoreBundle\Field\Types\ReferenceOfFieldType')->setArgument(5, $config['maximum_query_limit']);
    }
}
