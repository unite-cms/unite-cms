<?php

namespace UniteCMS\AdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use UniteCMS\AdminBundle\GraphQL\Extender\UniteAdminExtender;

class UniteCMSAdminExtension extends Extension
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->getDefinition(UniteAdminExtender::class)
            ->setArgument('$accessAdminViewsExpression', $config['access_admin_views_expression'])
            ->setArgument('$accessLogsExpression', $config['access_logs_expression'])
            ->setArgument('$accessSchemaFiles', $config['access_schema_files'])
            ->setArgument('$accessQueryExplorer', $config['access_query_explorer']);
    }
}
