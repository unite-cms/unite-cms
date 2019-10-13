<?php

namespace UniteCMS\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class UniteCMSCoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        if($container->getParameter('kernel.environment') === 'test') {
            $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/test'));
            $loader->load('services.yaml');
        }

        $defaultContentManager = null;
        $defaultUserManager = null;

        if(class_exists('UniteCMS\DoctrineORMBundle\Content\ContentManager')) {
            $defaultContentManager = 'UniteCMS\DoctrineORMBundle\Content\ContentManager';
        }

        if(class_exists('UniteCMS\DoctrineORMBundle\User\UserManager')) {
            $defaultUserManager = 'UniteCMS\DoctrineORMBundle\User\UserManager';
        }

        $configuration = new Configuration(
            $container->getParameter('kernel.project_dir') . '/config/unite/',
            SchemaManager::DEFAULT_BASE_SCHEMA,
            $defaultContentManager,
            $defaultUserManager
        );
        $config = $this->processConfiguration($configuration, $configs);

        foreach($config['domains'] as $id => $params) {
            $params['content_manager'] = substr($params['content_manager'], 0, 1) === '@' ? substr($params['content_manager'], 1) : $params['content_manager'];
            $config['domains'][$id]['content_manager'] = new Reference($params['content_manager']);

            $params['user_manager'] = substr($params['user_manager'], 0, 1) === '@' ? substr($params['user_manager'], 1) : $params['user_manager'];
            $config['domains'][$id]['user_manager'] = new Reference($params['user_manager']);

            foreach($params['schema'] as $s_key => $schemaFile) {
                $config['domains'][$id]['schema'][$s_key] = file_get_contents($schemaFile);
            }
        }

        $container->findDefinition(DomainManager::class)->setArgument('$domainConfig', $config['domains']);
    }
}
