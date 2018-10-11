<?php

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\View\ViewType;

class TableViewType extends ViewType
{
    const TYPE = "table";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Table/index.html.twig";

    const PROPERTY_IDENTIFIERS = ['id', 'locale', 'created', 'updated', 'deleted'];

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Returns the setting tree for this view. This is used to validate and process the view settings.
     *
     * @param View $view
     * @return NodeInterface
     */
    private function settingTree(View $view) : NodeInterface {

        $defaultTextField = $view->getContentType()->getFields()
        ->filter(function(ContentTypeField $field){
            return in_array($field->getType(), ['text', 'email']);
        })->map(function(ContentTypeField $field){
            return $field->getIdentifier();
        })->first();

        $tree = new TreeBuilder();
        $tree->root('settings')

            // Handle deprecated options
            ->beforeNormalization()
                ->always(function($v) use($defaultTextField) {

                    if(isset($v['sort_field']) || isset($v['sort_asc'])) {
                        $v['sort'] = [
                            'field' => $v['sort_field'] ?? null,
                            'asc' => $v['sort_asc'] ?? null,
                        ];

                        unset($v['sort_field']);
                        unset($v['sort_asc']);
                    }

                    if(isset($v['columns'])) {
                        if(is_array($v['columns'])) {
                            $v['fields'] = array_map(
                                function ($v) {
                                    return is_string($v) ? ['label' => $v] : $v;
                                },
                                $v['columns']
                            );
                        } else {
                            $v['fields'] = $v['columns'];
                        }
                        unset($v['columns']);
                    }

                    // Add default fields.
                    if(empty($v['fields'])) {
                        $v['fields'] = ['id', $defaultTextField, 'created', 'updated'];
                        if(!empty($v['sort']['field'])) {
                            array_unshift($v['fields'], $v['sort']['field']);
                        }
                        $v['fields'] = array_filter(array_unique($v['fields'], SORT_REGULAR));
                    }

                    // Add default sort field.
                    if(empty($v['sort'])) {
                        $v['sort'] = [
                            'field' => 'updated',
                            'asc' => false,
                        ];
                    }

                    return $v;
                })

            ->end()
            ->children()
                ->arrayNode('fields')
                    ->beforeNormalization()
                        ->ifArray()->then(function($v) use ($view) {
                            $transformed = [];
                            foreach ($v as $key => $value) {

                                // Allow to set fields as array of identifiers (["id", "title"])
                                if(is_numeric($key) && is_string($value)) {
                                    $key = $value;
                                    $value = [];
                                }

                                // Make sure, that key is defined.
                                $root_key = explode('.', $key)[0];
                                if(!in_array($key, static::PROPERTY_IDENTIFIERS) && !$view->getContentType()->getFields()->containsKey($root_key)) {
                                    $exception = new InvalidConfigurationException(sprintf('Unknown field %s', json_encode($key)));
                                    $exception->setPath($key);
                                    throw $exception;
                                }

                                $transformed[$key] = (array)$value + $this->defaultFieldConfig($key, $view);
                            }

                            return $transformed;
                        })
                    ->end()
                    ->useAttributeAsKey('field')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')->isRequired()->end()
                            ->scalarNode('label')->isRequired()->end()
                            ->variableNode('settings')->end()
                        ->end()
                    ->end()
                ->end()
                ->variableNode('filter')
                    ->validate()
                        ->always(function($v){

                            $exception = new InvalidConfigurationException('Invalid filter configuration');
                            $exception->setPath('filter');

                            if(!is_array($v)) {
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
                        })
                    ->end()
                ->end()
                ->arrayNode('sort')
                    ->children()
                        ->scalarNode('field')->end()
                        ->booleanNode('asc')->treatNullLike(false)->end()
                        ->booleanNode('sortable')->end()
                    ->end()
                ->end()

                ->scalarNode('sort_field')->setDeprecated()->end()
            ->end()
        ;

        return $tree->buildTree();
    }

    private function defaultFieldConfig(string $field, View $view) {

        // If this is a nested key, we cannot resolve it.
        if(count(explode('.', $field)) > 1) {
            return [];
        }

        if(in_array($field, static::PROPERTY_IDENTIFIERS)) {
            return [
                'type' => ($field == 'id' ? 'id' : ($field == 'locale' ? 'locale' : 'date')),
                'label' => ucfirst($field),
            ];
        }

        /**
         * @var ContentTypeField $field
         */
        $field = $view->getContentType()->getFields()->get($field);

        if($field) {
            return [
                'label' => $field->getTitle(),
                'type' => $field->getType(),
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $processor = new Processor();
        $settings = $processor->process($this->settingTree($view), $view->getSettings()->processableConfig());

        foreach($settings['fields'] as $identifier => $definition) {
            if(!in_array($identifier, static::PROPERTY_IDENTIFIERS)) {

                $field = $view->getContentType()->getFields()->get($identifier);
                $fieldType = $this->fieldTypeManager->getFieldType($definition['type']);

                if ($fieldType->getViewFieldConfig($field)) {
                    $settings['fields'][$identifier]['config'] = $fieldType->getViewFieldConfig($field);
                }

                if ($fieldType->getViewFieldAssets($field)) {
                    $settings['fields'][$identifier]['assets'] = $fieldType->getViewFieldAssets($field);
                }
            }
        }

        return array_merge($settings, [
            'View' => $view->getIdentifier(),
            'contentType' => IdentifierNormalizer::graphQLIdentifier($view->getContentType()),
            'hasTranslations' => count($view->getContentType()->getLocales()) > 1,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(ViewSettings $settings, ExecutionContextInterface $context)
    {
        $processor = new Processor();
        try {
            $processor->process($this->settingTree($context->getObject()), $settings->processableConfig());
        }
        catch (\Symfony\Component\Config\Definition\Exception\Exception $e) {
            $context->buildViolation($e->getMessage())->addViolation();
        }
    }
}
