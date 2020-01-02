<?php

namespace UniteCMS\CoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
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
        $defaultLogger = null;

        if(class_exists('UniteCMS\DoctrineORMBundle\Content\ContentManager')) {
            $defaultContentManager = 'UniteCMS\DoctrineORMBundle\Content\ContentManager';
        }

        if(class_exists('UniteCMS\DoctrineORMBundle\User\UserManager')) {
            $defaultUserManager = 'UniteCMS\DoctrineORMBundle\User\UserManager';
        }

        if(class_exists('UniteCMS\DoctrineORMBundle\Logger\Logger')) {
            $defaultLogger = 'UniteCMS\DoctrineORMBundle\Logger\Logger';
        }

        $configuration = new Configuration(
            $container->getParameter('kernel.project_dir') . '/config/unite/',
            [SchemaManager::UNITE_CMS_ROOT_SCHEMA],
            $defaultContentManager,
            $defaultUserManager,
            $defaultLogger
        );
        $config = $this->processConfiguration($configuration, $configs);

        foreach($config['domains'] as $id => $params) {
            $params['content_manager'] = substr($params['content_manager'], 0, 1) === '@' ? substr($params['content_manager'], 1) : $params['content_manager'];
            if(!empty($params['content_manager'])) {
                $config['domains'][$id]['content_manager'] = new Reference($params['content_manager']);
            }

            $params['user_manager'] = substr($params['user_manager'], 0, 1) === '@' ? substr($params['user_manager'], 1) : $params['user_manager'];
            if(!empty($params['user_manager'])) {
                $config['domains'][$id]['user_manager'] = new Reference($params['user_manager']);
            }

            $params['logger'] = substr($params['logger'], 0, 1) === '@' ? substr($params['logger'], 1) : $params['logger'];
            if(!empty($params['logger'])) {
                $config['domains'][$id]['logger'] = new Reference($params['logger']);
            }
        }

        $container->findDefinition(DomainManager::class)
            ->setArgument('$domainConfig', $config['domains'])
            ->setArgument('$isAdminExpression', $config['is_admin_expression']);
    }
}
