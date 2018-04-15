<?php

namespace UniteCMS\CollectionFieldBundle\Field\Types;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\ConstraintViolation;
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
    const TYPE = "collection";
    const FORM_TYPE = CollectionFormType::class;
    const SETTINGS = ['fields', 'min_rows', 'max_rows'];
    const REQUIRED_SETTINGS = ['fields'];

    private $validator;
    private $collectionFieldTypeFactory;
    private $fieldTypeManager;

    function __construct(
        ValidatorInterface $validator,
        CollectionFieldTypeFactory $collectionFieldTypeFactory,
        FieldTypeManager $fieldTypeManager
    ) {
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
        return array_merge(
            parent::getFormOptions($field),
            [
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype_name' => '__'.str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')).'Name__',
                'attr' => [
                    'data-identifier' => str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')),
                    'min-rows' => $settings->min_rows ?? 0,
                    'max-rows' => $settings->max_rows ?? 0,
                ],
                'entry_type' => FieldableFormType::class,
                'entry_options' => $options,
            ]
        );
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
    function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        // Validate allowed and required settings.
        $violations = parent::validateSettings($field, $settings);

        // Validate sub fields.
        if (empty($violations)) {

            // Validate a virtual fieldable for this collection field.
            foreach ($this->validator->validate(self::getNestableFieldable($field)) as $violation) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, $validation_group = 'DEFAULT'): array
    {
        // When deleting content, we don't need to validate data.
        if ($validation_group === 'DELETE') {
            return [];
        }

        $violations = $this->validateNestedFields($data, $field);

        $max_rows = $field->getSettings()->max_rows ?? 0;
        $min_rows = $field->getSettings()->min_rows ?? 0;

        // Validate max_rows
        if ($max_rows > 0 && $max_rows < count($data)) {
            $violations[] = $this->createViolation($field, 'validation.too_many_rows');
        }

        // Validate min_rows
        if (count($data) < $min_rows) {
            $violations[] = $this->createViolation($field, 'validation.too_few_rows');
        }

        return $violations;
    }

    /**
     * Recursively validate all fields in this collection.
     *
     * @param array $data
     * @param FieldableField $field
     *
     * @return array
     */
    private function validateNestedFields($data, FieldableField $field)
    {

        $violations = [];
        $collection = self::getNestableFieldable($field);

        // Make sure, that there is no additional data in content that is not in settings.
        foreach ($data as $row) {
            foreach (array_keys($row) as $data_key) {

                // If the field does not exists, add an error.
                if (!$collection->getFields()->containsKey($data_key)) {
                    $violations[] = new ConstraintViolation(
                        'validation.additional_data',
                        'validation.additional_data',
                        [],
                        $row,
                        join(
                            '.',
                            [
                                $field->getEntity()->getIdentifierPath('.'),
                                $field->getIdentifier(),
                                $data_key,
                            ]
                        ),
                        $row
                    );

                    // If the field exists, let the fieldTypeManager validate it.
                } else {
                    $violations = array_merge(
                        $violations,
                        $this->fieldTypeManager->validateFieldData(
                            $collection->getFields()->get($data_key),
                            $row[$data_key]
                        )
                    );
                }
            }
        }

        return $violations;
    }

    /**
     * Delegate onCreate call to all child fields, that implement it.
     *
     * @param FieldableField $field
     * @param Content $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onCreate(FieldableField $field, Content $content, EntityRepository $repository, &$data)
    {

        // If child field implements onCreate, call it!
        foreach (self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if (method_exists($fieldType, 'onCreate')) {
                if (!empty($data[$field->getIdentifier()])) {
                    foreach ($data[$field->getIdentifier()] as $key => $subData) {
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
     * @param $data
     */
    public function onUpdate(
        FieldableField $field,
        FieldableContent $content,
        EntityRepository $repository,
        $old_data,
        &$data
    ) {

        // If child field implements onUpdate, call it!
        foreach (self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if (method_exists($fieldType, 'onUpdate')) {

                $keys_used = [];

                // Call hook for all available data.
                if (!empty($data[$field->getIdentifier()])) {
                    foreach ($data[$field->getIdentifier()] as $key => $subData) {
                        $subOldData = isset($old_data[$field->getIdentifier()][$key]) ? $old_data[$field->getIdentifier(
                        )][$key] : [];
                        $fieldType->onUpdate($subField, $content, $repository, $subOldData, $subData);
                        $data[$field->getIdentifier()][$key] = $subData;
                        $keys_used[] = $key;
                    }
                }

                // Call hook for all available old data, but only if it was not called before.
                if (!empty($old_data[$field->getIdentifier()])) {
                    foreach ($old_data[$field->getIdentifier()] as $key => $subOldData) {
                        if (!in_array($key, $keys_used)) {
                            $subData = isset($data[$field->getIdentifier()][$key]) ? $data[$field->getIdentifier(
                            )][$key] : [];
                            $fieldType->onUpdate($subField, $content, $repository, $subOldData, $subData);
                            $data[$field->getIdentifier()][$key] = $subData;
                        }
                    }
                }
            }
        }
    }

    /**
     * Delegate onSoftDelete call to all child fields, that implement it.
     *
     * @param FieldableField $field
     * @param Content $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onSoftDelete(FieldableField $field, Content $content, EntityRepository $repository, $data)
    {

        // If child field implements onSoftDelete, call it!
        foreach (self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if (method_exists($fieldType, 'onSoftDelete')) {
                if (!empty($data[$field->getIdentifier()])) {
                    foreach ($data[$field->getIdentifier()] as $key => $subData) {
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
    public function onHardDelete(FieldableField $field, Content $content, EntityRepository $repository, $data)
    {

        // If child field implements onHardDelete, call it!
        foreach (self::getNestableFieldable($field)->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if (method_exists($fieldType, 'onHardDelete')) {
                if (!empty($data[$field->getIdentifier()])) {
                    foreach ($data[$field->getIdentifier()] as $key => $subData) {
                        $fieldType->onHardDelete($subField, $content, $repository, $subData);
                    }
                }
            }
        }
    }
}
