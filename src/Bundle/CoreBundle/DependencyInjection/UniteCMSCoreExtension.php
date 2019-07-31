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

        // Set domain_config_parameters to domain config manager.
        $container->getDefinition('unite.cms.domain_config_manager')->setArgument(4, $config['domain_config_parameters']);

        // Set maximum nesting level as 3rd argument to schema type manager.
        $container->getDefinition('unite.cms.graphql.schema_type_manager')->setArgument(2, $config['maximum_nesting_level']);

        // Set maximum query limit as sixth argument to query type and maximum reference of field type, and
        // as second argument to table/tree/grid view config factory classes.
        $container->getDefinition('UniteCMS\CoreBundle\SchemaType\Types\QueryType')->setArgument(5, $config['maximum_query_limit']);
        $container->getDefinition('UniteCMS\CoreBundle\Field\Types\ReferenceOfFieldType')->setArgument(6, $config['maximum_query_limit']);
        $container->getDefinition('UniteCMS\CoreBundle\View\Types\Factories\GridViewConfigurationFactory')->setArgument(1, $config['maximum_query_limit']);
        $container->getDefinition('UniteCMS\CoreBundle\View\Types\Factories\TableViewConfigurationFactory')->setArgument(1, $config['maximum_query_limit']);
        $container->getDefinition('UniteCMS\CoreBundle\View\Types\Factories\TreeViewConfigurationFactory')->setArgument(1, $config['maximum_query_limit']);
    }
}
