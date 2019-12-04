<?php


namespace UniteCMS\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use UniteCMS\CoreBundle\Log\LoggerInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_IS_ADMIN_EXPRESSION = 'has_role("ROLE_ADMIN")';
    const DEFAULT_JWT_TTL_SHORT_LIVING = 1800;          // 30 Minutes
    const DEFAULT_JWT_TTL_LONG_LIVING = 31536000;       // 1 Year

    /**
     * @var string $defaultConfigDir
     */
    protected $defaultConfigDir;

    /**
     * @var string[] $uniteCMSSchemaFiles
     */
    protected $uniteCMSSchemaFiles;

    /**
     * @var string $defaultContentManager
     */
    protected $defaultContentManager;

    /**
     * @var string $defaultUserManager
     */
    protected $defaultUserManager;

    /**
     * @var LoggerInterface $defaultLogger
     */
    protected $defaultLogger;

    public function __construct(string $defaultConfigDir, array $uniteCMSSchemaFiles = [], string $defaultContentManager = null, string $defaultUserManager = null, string $defaultLogger = null)
    {
        $this->defaultConfigDir = $defaultConfigDir;
        $this->uniteCMSSchemaFiles = $uniteCMSSchemaFiles;
        $this->defaultContentManager = $defaultContentManager;
        $this->defaultUserManager = $defaultUserManager;
        $this->defaultLogger = $defaultLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('unite_cms_core');
        $treeBuilder->getRootNode()
        ->children()
            ->scalarNode('is_admin_expression')
                ->cannotBeEmpty()
            ->defaultValue(static::DEFAULT_IS_ADMIN_EXPRESSION)
            ->end()
            ->scalarNode('default_schema_config_dir')
                ->cannotBeEmpty()
                ->defaultValue($this->defaultConfigDir)
            ->end()
            ->arrayNode('domains')
                ->addDefaultChildrenIfNoneSet('default')
                ->useAttributeAsKey('id')
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                    ->children()
                        ->arrayNode('schema')
                            ->beforeNormalization()
                                ->castToArray()
                            ->end()
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('editable_schema_files_directory')->end()
                        ->scalarNode('content_manager')
                            ->cannotBeEmpty()
                            ->defaultValue($this->defaultContentManager)
                        ->end()
                        ->scalarNode('user_manager')
                            ->cannotBeEmpty()
                            ->defaultValue($this->defaultUserManager)
                        ->end()
                        ->scalarNode('logger')
                            ->cannotBeEmpty()
                            ->defaultValue($this->defaultLogger)
                        ->end()
                        ->scalarNode('jwt_ttl_short_living')
                            ->cannotBeEmpty()
                            ->defaultValue(static::DEFAULT_JWT_TTL_SHORT_LIVING)
                        ->end()
                        ->scalarNode('jwt_ttl_long_living')
                            ->cannotBeEmpty()
                            ->defaultValue(static::DEFAULT_JWT_TTL_LONG_LIVING)
                        ->end()
                        ->arrayNode('parameters')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ->validate()
            ->always(function($v){

                // Set default schema file to all domain configurations.
                foreach($v['domains'] as $key => $domain) {
                    if(empty($domain['schema'])) {
                        $v['domains'][$key]['schema'] = $this->uniteCMSSchemaFiles;
                        $v['domains'][$key]['editable_schema_files_directory'] = sprintf('%s%s/', $v['default_schema_config_dir'], $key);
                    }
                }
                return $v;
            })
        ->end();
        return $treeBuilder;
    }
}
