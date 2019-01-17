<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.10.18
 * Time: 14:02
 */

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;

/**
 * Defines the configuration for the table view.
 */
class TableViewConfiguration implements ConfigurationInterface
{
    const PROPERTY_IDENTIFIERS = ['id', 'locale', 'created', 'updated', 'deleted'];

    /**
     * @var Fieldable $fieldable
     */
    protected $fieldable;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(Fieldable $fieldable, FieldTypeManager $fieldTypeManager)
    {
        $this->fieldable = $fieldable;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('settings');
        $treeBuilder->getRootNode()

            ->beforeNormalization()->always(\Closure::fromCallable([$this, 'handleDeprecatedConfig']))->end()
            ->beforeNormalization()->always(\Closure::fromCallable([$this, 'normalizeConfig']))->end()

            ->children()
                ->append($this->appendFieldsNode())
                ->append($this->appendFilterNode())
                ->append($this->appendSortNode())
                ->variableNode('sort_field')->setDeprecated()->end()
                ->variableNode('sort_asc')->setDeprecated()->end()
                ->variableNode('columns')->setDeprecated()->end()
            ->end();

        return $treeBuilder;
    }

    protected function appendFieldsNode() : ArrayNodeDefinition {
        $treeBuilder = new TreeBuilder('fields');
        return $treeBuilder->getRootNode()
            ->beforeNormalization()
                ->ifArray()->then(\Closure::fromCallable([$this, 'normalizeFields']))
            ->end()
            ->useAttributeAsKey('field')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('type')->isRequired()->end()
                    ->scalarNode('label')->isRequired()->end()
                    ->variableNode('settings')->end()
                    ->variableNode('assets')->end()
                ->end()
            ->end();
    }
    protected function appendFilterNode() : VariableNodeDefinition {
        $treeBuilder = new TreeBuilder('filter', 'variable');
        return $treeBuilder->getRootNode()
            ->validate()
                ->always(
                    function ($v) {

                        $exception = new InvalidConfigurationException('Invalid filter configuration');
                        $exception->setPath('filter');

                        if (!is_array($v)) {
                            throw $exception;
                        }

                        if (!empty(array_diff(array_keys($v), ['AND', 'OR', 'field', 'value', 'operator']))) {
                            throw $exception;
                        }

                        try {
                            $filter_structure = new GraphQLDoctrineFilterQueryBuilder($v, [], 'c');
                        } catch (\Exception $e) {
                            throw $exception;
                        }

                        if (!$filter_structure->getFilter()) {
                            throw $exception;
                        }

                        return $v;
                    }
                )
            ->end();
    }
    protected function appendSortNode() : ArrayNodeDefinition {
        $treeBuilder = new TreeBuilder('sort');
        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('field')->isRequired()->end()
                ->booleanNode('asc')->treatNullLike(false)->end()
                ->booleanNode('sortable')->end()
            ->end();
    }

    /**
     * @deprecated 1.0
     */
    protected function handleDeprecatedConfig(array $v) : array {

        if (isset($v['sort_field']) || isset($v['sort_asc'])) {
            $v['sort'] = [
                'field' => $v['sort_field'] ?? null,
                'asc' => $v['sort_asc'] ?? null,
            ];
        }

        if (isset($v['columns'])) {
            if (is_array($v['columns'])) {
                $v['fields'] = array_map(
                    function ($v) {
                        return is_string($v) ? ['label' => $v] : $v;
                    },
                    $v['columns']
                );
            } else {
                $v['fields'] = $v['columns'];
            }
        }

        return $v;
    }

    protected function normalizeConfig(array $v) : array {
        // Add default fields.
        if (empty($v['fields'])) {
            $v['fields'] = $this->addDefaultFields($v);
        }

        // Add default sort field.
        if (empty($v['sort'])) {
            $v['sort'] = [
                'field' => 'updated',
                'asc' => false,
            ];
        }

        else {
            if(!empty($v['sort']['sortable'])) {
                $v['sort']['asc'] = true;
            }
        }

        // Make sure that filter and sortable are not set both
        if(!empty($v['filter']) && !empty($v['sort']['sortable'])) {
            throw new InvalidConfigurationException('A sortable view cannot have filters set');
        }

        return $v;
    }

    protected function addDefaultFields($v) : array {

        $defaultTextField = $this->fieldable->getFields()
            ->filter(
                function (FieldableField $field) {
                    return in_array($field->getType(), ['text', 'email']);
                }
            )->map(
                function (FieldableField $field) {
                    return $field->getIdentifier();
                }
            )->first();

        $v['fields'] = ['id', $defaultTextField, 'created', 'updated'];
        if (!empty($v['sort']['field'])) {
            array_unshift($v['fields'], $v['sort']['field']);
        }
        return array_filter(array_unique($v['fields'], SORT_REGULAR));
    }

    protected function normalizeFields(array $fields) : array {
        $transformed = [];
        foreach ($fields as $key => $value) {

            // Allow to set fields as array of identifiers (["id", "title"])
            if (is_numeric($key) && is_string($value)) {
                $key = $value;
                $value = [];
            }

            // Allow to set fields as array of identifiers and labels (["id" => "ID", "title" => "Title"])
            if (is_string($key) && is_string($value)) {
                $value = ['label' => $value];
            }

            // Handle deprecated nested field selectors.
            $parts = explode('.', $key);
            if(count($parts) > 1) {
                $key = $parts[0];
                $value['settings'] = $value['settings'] ?? [];
                $value['settings']['fields'] = [$parts[1] => [
                    'type' => $value['type'],
                ]];
                unset($value['type']);
            }


            // Make sure, that key is defined.
            if (!in_array($key, self::PROPERTY_IDENTIFIERS) && !$this->fieldable->getFields()->exists(function($fkey, FieldableField $field) use($key) {
                return $field->getIdentifier() === $key;
            })) {
                $exception = new InvalidConfigurationException(sprintf('Unknown field %s', json_encode($key)));
                $exception->setPath($key);
                throw $exception;
            }

            // Allow to override all default fields from config
            $transformed[$key] = $this->defaultFieldConfig((array)$value, $key);
        }
        return $transformed;
    }

    private function defaultFieldConfig(array $config, string $field) : array
    {
        // Handle content type properties.
        if($this->fieldable instanceof ContentType && in_array($field, self::PROPERTY_IDENTIFIERS)) {
            $config['type'] = $config['type'] ?? ($field == 'id' ? 'id' : ($field == 'locale' ? 'locale' : 'date'));
            $config['label'] = $config['label'] ?? ucfirst($field);
            return $config;
        }

        /**
         * @var FieldableField $field
         */
        $fieldEntity = $this->fieldable->getFields()->filter(function(FieldableField $fieldEntity) use ($field) { return $fieldEntity->getIdentifier() === $field; })->first();

        if(!$fieldEntity) {
            $exception = new InvalidConfigurationException(sprintf('Unknown field %s', json_encode($field)));
            $exception->setPath($field);
            throw $exception;
        }

        $type = $config['type'] ?? $fieldEntity->getType();
        $this->fieldTypeManager->getFieldType($type)->alterViewFieldSettings($config, $this->fieldTypeManager, $fieldEntity);
        return $config;
    }
}