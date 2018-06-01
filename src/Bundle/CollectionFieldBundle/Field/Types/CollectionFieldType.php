<?php

namespace UniteCMS\CollectionFieldBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CollectionFieldBundle\Form\CollectionFormType;
use UniteCMS\CollectionFieldBundle\Model\Collection;
use UniteCMS\CollectionFieldBundle\SchemaType\Factories\CollectionFieldTypeFactory;
use UniteCMS\CoreBundle\Entity\Content;
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

class CollectionFieldType extends FieldType implements NestableFieldTypeInterface
{
    const TYPE                      = "collection";
    const FORM_TYPE                 = CollectionFormType::class;
    const SETTINGS                  = ['fields', 'min_rows', 'max_rows'];
    const REQUIRED_SETTINGS         = ['fields'];

    private $validator;
    private $collectionFieldTypeFactory;
    private $fieldTypeManager;

    function __construct(ValidatorInterface $validator, CollectionFieldTypeFactory $collectionFieldTypeFactory, FieldTypeManager $fieldTypeManager)
    {
        $this->validator = $validator;
        $this->collectionFieldTypeFactory = $collectionFieldTypeFactory;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $settings = $field->getSettings();

        $options = [
            'label' => false,
        ];
        $options['fields'] = [];

        // Create a new collection model that implements Fieldable.
        $collection = self::getNestableFieldable($field);

        // Add the definition of the all collection fields to the options.
        foreach ($collection->getFields() as $fieldDefinition) {
            $options['fields'][] = new FieldableFormField(
                $this->fieldTypeManager->getFieldType($fieldDefinition->getType()),
                $fieldDefinition
            );
        }

        // Configure the collection from type.
        return array_merge(parent::getFormOptions($field), [
            'allow_add' => true,
            'allow_delete' => true,
            'delete_empty' => true,
            'prototype_name' => '__' . str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')) . 'Name__',
            'attr' => [
                'data-identifier' => str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')),
                'min-rows' => $settings->min_rows ?? 0,
                'max-rows' => $settings->max_rows ?? 0,
            ],
            'entry_type' => FieldableFormType::class,
            'entry_options' => $options,
        ]);
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
    function resolveGraphQLData(FieldableField $field, $value)
    {
        return (array)$value;
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
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context)
    {
        // When deleting content, we don't need to validate data.
        if(strtoupper($context->getGroup()) === 'DELETE') {
            return;
        }

        $this->validateNestedFields($field, $data, $context);

        $max_rows = $field->getSettings()->max_rows ?? 0;
        $min_rows = $field->getSettings()->min_rows ?? 0;

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
     * Recursively validate all fields in this collection.
     *
     * @param FieldableField $field
     * @param array $data
     * @param ExecutionContextInterface $context
     */
    private function validateNestedFields(FieldableField $field, $data, ExecutionContextInterface $context) {

        $collection = self::getNestableFieldable($field);

        // Make sure, that there is no additional data in content that is not in settings.
        foreach($data as $row) {
            foreach (array_keys($row) as $data_key) {

                // If the field does not exists, add an error.
                if (!$collection->getFields()->containsKey($data_key)) {
                    $context->buildViolation('additional_data')->atPath('['.join('][', [
                        $field->getEntity()->getIdentifierPath(']['),
                        $field->getIdentifier(),
                        $data_key
                    ]).']')->addViolation();

                // If the field exists, let the fieldTypeManager validate it.
                } else {
                    $this->fieldTypeManager->validateFieldData($collection->getFields()->get($data_key), $row[$data_key], $context);
                }
            }
        }
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
     * @param Content $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onCreate(FieldableField $field, Content $content, EntityRepository $repository, &$data) {

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
                foreach($old_data[$field->getIdentifier()] as $key => $subOldData) {
                    if(!empty($old_data[$field->getIdentifier()][$key]) && empty($data[$field->getIdentifier()][$key])) {
                        $fieldType->onHardDelete($subField, $content, $repository, $subOldData);
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
     * @param Content $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onSoftDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {

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
     * @param Content $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onHardDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {

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
}
