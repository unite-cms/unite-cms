<?php

namespace UniteCMS\CollectionFieldBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CollectionFieldBundle\Form\CollectionFormType;
use UniteCMS\CollectionFieldBundle\Model\Collection;
use UniteCMS\CollectionFieldBundle\Model\CollectionRow;
use UniteCMS\CollectionFieldBundle\SchemaType\Factories\CollectionFieldTypeFactory;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\NestableFieldTypeInterface;
use UniteCMS\CoreBundle\Form\FieldableFormField;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\View\Types\TableViewConfiguration;

class CollectionFieldType extends FieldType implements NestableFieldTypeInterface
{
    const TYPE                      = "collection";
    const FORM_TYPE                 = CollectionFormType::class;
    const SETTINGS                  = ['description', 'fields', 'min_rows', 'max_rows'];
    const REQUIRED_SETTINGS         = ['fields'];

    private $collectionFieldTypeFactory;
    private $fieldTypeManager;

    function __construct(CollectionFieldTypeFactory $collectionFieldTypeFactory, FieldTypeManager $fieldTypeManager)
    {
        $this->collectionFieldTypeFactory = $collectionFieldTypeFactory;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $settings = $field->getSettings();

        // Create a new collection model that implements Fieldable.
        $collection = self::getNestableFieldable($field);

        $options = [
            'label' => false,
            'content' => new CollectionRow($collection, [], null),
        ];
        $options['fields'] = [];

        // Add the definition of the all collection fields to the options.
        foreach ($collection->getFields() as $fieldDefinition) {
            $options['fields'][] = new FieldableFormField(
                $this->fieldTypeManager->getFieldType($fieldDefinition->getType()),
                $fieldDefinition
            );
        }

        // Configure the collection from type.
        return array_merge(
            parent::getFormOptions($field),
            [
                'required' => true,         // Please see CollectionFormType::buildView() for more information.
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'error_bubbling' => false,
                'prototype_name' => '__'.str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')).'Name__',
                'attr' => [
                    'data-identifier' => str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')),
                    'min-rows' => $settings->min_rows ?? 0,
                    'max-rows' => $settings->max_rows ?? null,
                ],
                'entry_type' => FieldableFormType::class,
                'entry_options' => $options,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $this->collectionFieldTypeFactory->createCollectionFieldType(
            $schemaTypeManager,
            $nestingLevel,
            $field,
            self::getNestableFieldable($field)
        );
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $this->collectionFieldTypeFactory->createCollectionFieldType(
            $schemaTypeManager,
            $nestingLevel,
            $field,
            self::getNestableFieldable($field),
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        return array_map(function($row) use($content) {
            return [
                'value' => $row,
                'content' => $content,
            ];
        }, (array)$value);
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        $field = $context->getObject();

        // Validate virtual sub fields for this collection.
        if($context->getViolations()->count() == 0 && $field instanceof FieldableField) {
            $context->getValidator()->inContext($context)->validate(self::getNestableFieldable($field));
        }
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field) { return []; }

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {}

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context)
    {
        // When deleting content, we don't need to validate data.
        if(strtoupper($context->getGroup()) === 'DELETE') {
            return;
        }

        $this->validateNestedFields($field, $data, $context);

        $max_rows = (int)$field->getSettings()->max_rows ?? 0;
        $min_rows = (int)$field->getSettings()->min_rows ?? 0;

        // Validate max_rows
        if($max_rows > 0 && $max_rows < count($data)) {
            $context->buildViolation('collectionfield.too_many_rows', ['%count%' => $max_rows])->atPath('['.$field->getIdentifier().']')->addViolation();
        }

        // Validate min_rows
        if(count($data) < $min_rows) {
            $context->buildViolation('collectionfield.too_few_rows', ['%count%' => $min_rows])->atPath('['.$field->getIdentifier().']')->addViolation();
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData)
    {
        if(empty($data[$field->getIdentifier()])) {
            return;
        }

        $collection = self::getNestableFieldable($field);

        // Alter data for each row.
        foreach($data[$field->getIdentifier()] as $delta => $row) {
            if(is_array($row)) {
                $row_data = $row;

                foreach ($collection->getFields() as $row_field) {
                    $this->fieldTypeManager->alterFieldData($row_field, $row_data, new CollectionRow($collection, $data[$field->getIdentifier()], $content, $delta), $rootData);
                }

                if($row_data != $row) {
                    $data[$field->getIdentifier()][$delta] = $row_data;
                }
            }
        }
    }

    /**
     * Recursively validate all fields in this collection.
     *
     * @param FieldableField $field
     * @param array $data
     * @param ExecutionContextInterface $context
     */
    private function validateNestedFields(FieldableField $field, $data, ExecutionContextInterface $context) {

        $collection = self::getNestableFieldable($field);
        $path = $context->getPropertyPath() . '[' . $collection->getIdentifier() . ']';
        $current_property_path = $context->getPropertyPath();
        $current_object = $context->getObject();

        // Make sure, that there is no additional data in content that is not in settings.
        foreach($data as $delta => $row) {

            $context->setNode($context->getValue(), new CollectionRow($collection, $data, $current_object, $delta), $context->getMetadata(), $path . '['.$delta.']');

            foreach (array_keys($row) as $data_key) {

                // If the field does not exists, add an error.
                if (!$collection->getFields()->containsKey($data_key)) {
                    $context->buildViolation('additional_data')->atPath('[' . $data_key .']')->addViolation();

                // If the field exists, let the fieldTypeManager validate it.
                } else {
                    $this->fieldTypeManager->validateFieldData($collection->getFields()->get($data_key), $row[$data_key], $context);
                }
            }
        }

        // Reset propertypath to the original value.
        $context->setNode($context->getValue(), $current_object, $context->getMetadata(), $current_property_path);
    }

    /**
     * @param FieldableField $field
     * @return Collection
     */
    static function getNestableFieldable(FieldableField $field): Fieldable
    {
        return new Collection(
          isset($field->getSettings()->fields) ? $field->getSettings()->fields : [],
          $field->getIdentifier(),
          $field->getEntity()
        );
    }

    /**
     * Delegate onCreate call to all child fields, that implement it.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {

        // If child field implements onCreate, call it!
        foreach(self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if(method_exists($fieldType, 'onCreate')) {
                if(!empty($data[$field->getIdentifier()])) {
                    foreach($data[$field->getIdentifier()] as $key => $subData) {
                        $fieldType->onCreate($subField, $content, $repository, $subData);
                        $data[$field->getIdentifier()][$key] = $subData;
                    }
                }
            }
        }
    }

    /**
     * Delegate onUpdate call to all child fields, that implement it.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @param EntityRepository $repository
     * @param $old_data
     * @param $data
     */
    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {

        // Depending on if the update adds, deletes, or modifies a row we need to call different hooks on each sub field.
        foreach(self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            // Case 1: row was not present in old data but is present in data: CREATE
            if(method_exists($fieldType, 'onCreate')) {
                foreach($data[$field->getIdentifier()] as $key => $subData) {
                    if(empty($old_data[$field->getIdentifier()][$key]) && !empty($data[$field->getIdentifier()][$key])) {
                        $subData = $data[$field->getIdentifier()][$key];
                        $fieldType->onCreate($subField, $content, $repository, $subData);
                        $data[$field->getIdentifier()][$key] = $subData;
                    }
                }
            }

            // Case 2: row was present in old data and is present in new data: UPDATE
            if(method_exists($fieldType, 'onUpdate')) {
                foreach($data[$field->getIdentifier()] as $key => $subData) {
                    if(!empty($old_data[$field->getIdentifier()][$key]) && !empty($data[$field->getIdentifier()][$key])) {
                        $subOldData = $old_data[$field->getIdentifier()][$key];
                        $subData = $data[$field->getIdentifier()][$key];

                        if($subOldData != $subData) {
                            $fieldType->onUpdate($subField, $content, $repository, $subOldData, $subData);
                            $data[$field->getIdentifier()][$key] = $subData;
                        }
                    }
                }
            }

            // Case 3: row was present in old data but is not present in new data: HARD DELETE
            if(method_exists($fieldType, 'onHardDelete')) {
                if(isset($old_data[$field->getIdentifier()])) {
                    foreach ($old_data[$field->getIdentifier()] as $key => $subOldData) {
                        if (!empty($old_data[$field->getIdentifier()][$key]) && empty(
                            $data[$field->getIdentifier()][$key]
                            )) {
                            $fieldType->onHardDelete($subField, $content, $repository, $subOldData);
                        }
                    }
                }
            }
        }

        // It can happen, that an index of data was deleted. However, when we store the data to the database, we want
        // to make sure, that there is no missing index for the rows array. Otherwise it would be treated as object
        // with numeric keys instead of an array.
        $data[$field->getIdentifier()] = array_values($data[$field->getIdentifier()]);
    }

    /**
     * Delegate onSoftDelete call to all child fields, that implement it.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {

        // If child field implements onSoftDelete, call it!
        foreach(self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if(method_exists($fieldType, 'onSoftDelete')) {
                if(!empty($data[$field->getIdentifier()])) {
                    foreach($data[$field->getIdentifier()] as $key => $subData) {
                        $fieldType->onSoftDelete($subField, $content, $repository, $subData);
                    }
                }
            }
        }
    }

    /**
     * Delegate onHardDelete call to all child fields, that implement it.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {

        // If child field implements onHardDelete, call it!
        foreach(self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if(method_exists($fieldType, 'onHardDelete')) {
                if(!empty($data[$field->getIdentifier()])) {
                    foreach($data[$field->getIdentifier()] as $key => $subData) {
                        $fieldType->onHardDelete($subField, $content, $repository, $subData);
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);

        $settings['assets'] = [
            [ 'js' => 'main.js', 'package' => 'UniteCMSCollectionFieldBundle' ],
            [ 'css' => 'main.css', 'package' => 'UniteCMSCollectionFieldBundle' ],
        ];

        if($field) {
            $settings['settings'] = $settings['settings'] ?? [];
            $settings['settings']['content_type'] = $field->getEntity()->getIdentifier();
            $settings['settings']['variant_titles'] = [];
            $settings['settings']['fields'] = $settings['settings']['fields'] ?? [];

            // normalize settings for nested fields.
            if(!empty($settings['settings']['fields'])) {
                $processor = new Processor();
                $config = $processor->processConfiguration(new TableViewConfiguration(self::getNestableFieldable($field), $fieldTypeManager), ['settings' => ['fields' => $settings['settings']['fields']]]);
                $settings['settings']['fields'] = $config['fields'];

                // Template will only include assets from root fields, so we need to add any child templates to the root field.
                foreach($config['fields'] as $nestedField) {
                    if(!empty($nestedField['assets'])) {
                        $settings['assets'] = array_merge($settings['assets'], $nestedField['assets']);
                    }
                }
            }
        }
    }
}
