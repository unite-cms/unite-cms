<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.10.18
 * Time: 14:02
 */

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;

/**
 * Defines the configuration for the table view.
 */
class TableViewConfiguration implements ConfigurationInterface
{
    const PROPERTY_IDENTIFIERS = ['id', 'locale', 'created', 'updated', 'deleted'];

    /**
     * @var View $view
     */
    private $view;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(View $view, FieldTypeManager $fieldTypeManager)
    {
        $this->view = $view;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $defaultTextField = $this->view->getContentType()->getFields()
            ->filter(
                function (ContentTypeField $field) {
                    return in_array($field->getType(), ['text', 'email']);
                }
            )->map(
                function (ContentTypeField $field) {
                    return $field->getIdentifier();
                }
            )->first();

        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('settings')
            // Handle deprecated options
            ->beforeNormalization()
                ->always(
                function ($v) use ($defaultTextField) {

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

                    // Add default fields.
                    if (empty($v['fields'])) {
                        $v['fields'] = ['id', $defaultTextField, 'created', 'updated'];
                        if (!empty($v['sort']['field'])) {
                            array_unshift($v['fields'], $v['sort']['field']);
                        }
                        $v['fields'] = array_filter(array_unique($v['fields'], SORT_REGULAR));
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
            )
            ->end()
            ->children()
                ->arrayNode('fields')

                ->beforeNormalization()
                    ->ifArray()->then(
                function ($v) {
                    $transformed = [];
                    foreach ($v as $key => $value) {

                        // Allow to set fields as array of identifiers (["id", "title"])
                        if (is_numeric($key) && is_string($value)) {
                            $key = $value;
                            $value = [];
                        }

                        // Allow to set fields as array of identifiers and labels (["id" => "ID", "title" => "Title"])
                        if (is_string($key) && is_string($value)) {
                            $value = ['label' => $value];
                        }

                        // Make sure, that key is defined.
                        $root_key = explode('.', $key)[0];
                        if (!in_array($key, self::PROPERTY_IDENTIFIERS) && !$this->view->getContentType()->getFields(
                            )->containsKey($root_key)) {
                            $exception = new InvalidConfigurationException(
                                sprintf('Unknown field %s', json_encode($key))
                            );
                            $exception->setPath($key);
                            throw $exception;
                        }

                        $transformed[$key] = (array)$value + $this->defaultFieldConfig($key, $value['type'] ?? null);
                    }

                    return $transformed;
                }
            )
                ->end()

                ->useAttributeAsKey('field')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('type')->isRequired()->end()
                        ->scalarNode('label')->isRequired()->end()
                        ->variableNode('settings')->end()
                        ->variableNode('assets')->end()
                    ->end()
                ->end()
            ->end()

            ->variableNode('filter')
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
                ->end()
            ->end()
            ->arrayNode('sort')
                ->children()
                    ->scalarNode('field')->isRequired()->end()
                    ->booleanNode('asc')->treatNullLike(false)->end()
                    ->booleanNode('sortable')->end()
                ->end()
            ->end()

            ->variableNode('sort_field')->setDeprecated()->end()
            ->variableNode('sort_asc')->setDeprecated()->end()
            ->variableNode('columns')->setDeprecated()->end()
        ->end();

        return $treeBuilder;
    }

    private function defaultFieldConfig(string $field, string $type = null)
    {

        // If this is a nested key, we cannot resolve it.
        if (count(explode('.', $field)) > 1) {
            return [];
        }

        if (in_array($field, self::PROPERTY_IDENTIFIERS)) {
            return [
                'type' => ($field == 'id' ? 'id' : ($field == 'locale' ? 'locale' : 'date')),
                'label' => ucfirst($field),
            ];
        }

        /**
         * @var ContentTypeField $field
         */
        $field = $this->view->getContentType()->getFields()->get($field);

        if ($field) {
            return $this->fieldTypeManager->getFieldType($field->getType())->getViewFieldDefinition($field);
        }

        elseif(!empty($type)) {
            return $this->fieldTypeManager->getFieldType($type);
        }

        return [];
    }
}