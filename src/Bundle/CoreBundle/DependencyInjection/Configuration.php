<?php


namespace UniteCMS\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var string $defaultConfigDir
     */
    protected $defaultConfigDir;

    /**
     * @var string $defaultBaseSchema
     */
    protected $defaultBaseSchema;

    /**
     * @var string $defaultContentManager
     */
    protected $defaultContentManager;

    /**
     * @var string $defaultUserManager
     */
    protected $defaultUserManager;

    public function __construct(string $defaultConfigDir, string $defaultBaseSchema, string $defaultContentManager = null, string $defaultUserManager = null)
    {
        $this->defaultConfigDir = $defaultConfigDir;
        $this->defaultBaseSchema = $defaultBaseSchema;
        $this->defaultContentManager = $defaultContentManager;
        $this->defaultUserManager = $defaultUserManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('unite_cms_core');
        $treeBuilder->getRootNode()
        ->children()
            ->scalarNode('default_schema_config_dir')
                ->cannotBeEmpty()
                ->defaultValue($this->defaultConfigDir)
            ->end()
            ->scalarNode('base_schema')
                ->cannotBeEmpty()
                ->defaultValue($this->defaultBaseSchema)
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
                        ->scalarNode('content_manager')
                            ->cannotBeEmpty()
                            ->isRequired()
                            ->defaultValue($this->defaultContentManager)
                        ->end()
                        ->scalarNode('user_manager')
                            ->cannotBeEmpty()
                            ->defaultValue($this->defaultUserManager)
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
                        $v['domains'][$key]['schema'][] = $v['base_schema'];
                        $v['domains'][$key]['schema'][] = sprintf('%s%s.graphql', $v['default_schema_config_dir'], $key);
                    }
                }
                return $v;
            })
        ->end();
        return $treeBuilder;
    }
}
