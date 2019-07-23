<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.08.18
 * Time: 14:08
 */

namespace UniteCMS\VariantsFieldBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\NestableFieldTypeInterface;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\View\Types\Factories\ViewConfigurationFactoryInterface;
use UniteCMS\VariantsFieldBundle\Form\VariantsFormType;
use UniteCMS\VariantsFieldBundle\Model\Variant;
use UniteCMS\VariantsFieldBundle\Model\VariantContent;
use UniteCMS\VariantsFieldBundle\Model\Variants;
use UniteCMS\VariantsFieldBundle\SchemaType\Factories\VariantFactory;

class VariantsFieldType extends FieldType implements NestableFieldTypeInterface
{
    const TYPE                      = "variants";
    const FORM_TYPE                 = VariantsFormType::class;
    const SETTINGS                  = ["description", 'variants', 'form_group', 'not_empty'];
    const REQUIRED_SETTINGS         = ['variants'];

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    /**
     * @var VariantFactory $variantFactory
     */
    private $variantFactory;

    /**
     * @var ViewConfigurationFactoryInterface $tableViewConfigurationFactory;
     */
    protected $tableViewConfigurationFactory;

    function __construct(FieldTypeManager $fieldTypeManager, VariantFactory $variantFactory, ViewConfigurationFactoryInterface $tableViewConfigurationFactory)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->variantFactory = $variantFactory;
        $this->tableViewConfigurationFactory = $tableViewConfigurationFactory;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        // Configure the variants from type.
        return array_merge(parent::getFormOptions($field), [
            'not_empty' => $field->getSettings()->not_empty ?? false,
            'required' => true,
            'variants' => self::getNestableFieldable($field),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        $variants = self::getNestableFieldable($field);
        foreach($variants->getVariantsMetadata() as $meta) {

            // Creates a new schema type object for this variant and register it to schema type manager.
            $this->variantFactory->createVariantType($schemaTypeManager, new Variant(
                null,
                $variants->getFieldsForVariant($meta['identifier']),
                $meta['identifier'],
                $meta['title'],
                $variants
            ));
        }

        return $schemaTypeManager->getSchemaType('VariantsFieldInterface', $field->getEntity()->getRootEntity()->getDomain());
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return $this->variantFactory->createVariantsInputType($schemaTypeManager, self::getNestableFieldable($field));
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        if(empty($value['type'])) {
            return new Variant($content);
        }

        $variants = self::getNestableFieldable($field);

        return new Variant(
            $content,
            $variants->getFieldsForVariant($value['type']),
            $value['type'],
            $value['type'],
            $variants,
            $value[$value['type']] ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Let parent validate allowed and required root level settings.
        parent::validateSettings($settings, $context);
        if($context->getViolations()->count() > 0) {
            return;
        }

        // Variants must be defined as array.
        if(!is_array($settings->variants)) {
            $context->buildViolation('variantsfield.not_an_array')->atPath('variants')->addViolation();
            return;
        }

        // Variants must not be empty.
        if(empty($settings->variants)) {
            $context->buildViolation('required')->atPath('variants')->addViolation();
            return;
        }

        $taken_identifiers = [];

        foreach($settings->variants as $delta => $variant) {
            $this->validateVariant($variant, $delta, $context, $taken_identifiers);
        }

        if($context->getViolations()->count() > 0) {
            return;
        }

        // Validate virtual sub fields for all variants.
        $field = $context->getObject();

        if($field instanceof FieldableField) {

            /**
             * @var Variants $variants
             */
            $variants = self::getNestableFieldable($field);

            foreach($variants->getVariantsMetadata() as $delta => $meta) {

                $variant = new Variant(
                    null,
                    $variants->getFieldsForVariant($meta['identifier']),
                    $meta['identifier'],
                    $meta['title'],
                    $field
                );

                $context->getValidator()->inContext($context)->atPath('variants['.$delta.']')->validate($variant);

            }
        }
    }

    /**
     * Validates a single variant setting.
     * @param $variant
     * @param $delta
     * @param ExecutionContextInterface $context
     * @param $taken_identifiers
     */
    function validateVariant($variant, $delta, ExecutionContextInterface $context, &$taken_identifiers) {

        $path = 'variants[' . $delta . '].';

        // Check that only allowed settings are present.
        foreach (array_keys($variant) as $setting) {
            if (!in_array($setting, ['title', 'identifier', 'settings', 'icon', 'fields'])) {
                $context->buildViolation('additional_data')->atPath($path . $setting)->addViolation();
            }
        }

        // Check that only allowed settings are present.
        if(!empty($variant['settings'])) {
            if(!is_array($variant['settings'])) {
                $context->buildViolation('not_an_array')->atPath($path.'[settings]')->addViolation();
            } else {
                foreach (array_keys($variant['settings']) as $sub_setting) {
                    if (!in_array($sub_setting, ['description'])) {
                        $context->buildViolation('additional_data')->atPath($path.'[settings]'.$sub_setting)->addViolation();
                    }
                }
            }
        }

        // Check that all required settings are present.
        foreach (['title', 'identifier', 'fields'] as $setting) {
            if (!isset($variant[$setting])) {
                $context->buildViolation('required')->atPath($path . $setting)->addViolation();
            }
        }

        if($context->getViolations()->count() > 0) {
            return;
        }

        // Check that variant identifier is not "type".
        if($variant['identifier'] === 'type') {
            $context->buildViolation('reserved_identifier')->atPath($path . 'identifier')->addViolation();
        }

        // Check that variant identifier is not already taken.
        if(in_array($variant['identifier'], $taken_identifiers)) {
            $context->buildViolation('identifier_already_taken')->atPath($path . 'identifier')->addViolation();
        } else {
            $taken_identifiers[] = $variant['identifier'];
        }

        // Check that fields is an array.
        if(!is_array($variant['fields'])) {
            $context->buildViolation('variantsfield.not_an_array')->atPath($path . 'fields')->addViolation();
            return;
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

        // If type is not set, this field is not filled out.
        if(empty($data['type'])) {

            if(!empty($field->getSettings()->not_empty)) {
                $context->buildViolation('required')->atPath('['.$field->getIdentifier().']')->addViolation();
            }

            return;
        }

        if(empty($data[$data['type']])) {
            $data[$data['type']] = [];
        }

        // Validate all fields for the given type.
        $variants = self::getNestableFieldable($field);
        $fields_per_key = [];
        foreach($variants->getFieldsForVariant($data['type']) as $field) {
            $fields_per_key[$field->getIdentifier()] = $field;
        }

        // variant fields have at least 3 identifier path elements (field, variant and variants).
        $context->setNode($context->getValue(), null, $context->getMetadata(), 'data[' . $field->getEntity()->getIdentifierPath('][', false) . ']');

        foreach($data[$data['type']] as $field_key => $field_value) {

            if(!array_key_exists($field_key, $fields_per_key)) {
                $context->buildViolation('additional_data')->atPath('['.$field_key.']')->addViolation();
            } else {
                $this->fieldTypeManager->validateFieldData($fields_per_key[$field_key], $field_value, $context);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData)
    {
        if(empty($data[$field->getIdentifier()]) || empty($data[$field->getIdentifier()]['type'])) {
            $data[$field->getIdentifier()] = [
                'type' => null,
            ];
            return;
        }

        $type = $data[$field->getIdentifier()]['type'];
        $variant_data = empty($data[$field->getIdentifier()][$type]) ? [] : $data[$field->getIdentifier()][$type];

        // Alter all fields for the given type.
        $variants = self::getNestableFieldable($field);

        foreach($variants->getFieldsForVariant($type) as $row_field) {
            $variant = new Variant($content, $variants->getFieldsForVariant($row_field->getIdentifier()), $row_field->getIdentifier(), $row_field->getTitle(), $variants);
            $this->fieldTypeManager->alterFieldData($row_field, $variant_data, new VariantContent($variant, $variant_data), $rootData);
        }

        if($variant_data != $data[$field->getIdentifier()][$type]) {
            $data[$field->getIdentifier()][$type] = $variant_data;
        }
    }

    /**
     * @param FieldableField $field
     * @return Variants
     */
    static function getNestableFieldable(FieldableField $field): Fieldable
    {
        return new Variants(
            $field->getSettings()->variants,
            $field->getIdentifier(),
            $field->getEntity()
        );
    }

    /**
     * Creates a virtual variant model for the given field and a variant identifier.
     * @param FieldableContent $content
     * @param FieldableField $field
     * @param string $variant
     * @param array $data
     * @return Variant
     */
    static function createVariant(FieldableContent $content, FieldableField $field, string $variant, array $data = []) : Variant {
        $variants = self::getNestableFieldable($field);
        return new Variant(
            $content,
            $variants->getFieldsForVariant($variant),
            $variant,
            $variant,
            $variants,
            $data
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {

        // Only continue if we have data and a type for this field.
        if(empty($data[$field->getIdentifier()]) || empty($data[$field->getIdentifier()]['type']) || empty($data[$field->getIdentifier()][$data[$field->getIdentifier()]['type']])) {
            return;
        }

        $variant = static::createVariant($content, $field, $data[$field->getIdentifier()]['type'], $data[$field->getIdentifier()][$data[$field->getIdentifier()]['type']]);

        // If child field implements onCreate, call it!
        foreach($variant->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if(method_exists($fieldType, 'onCreate')) {
                if(isset($variant->getData()[$subField->getIdentifier()])) {
                    $variantData = $variant->getData();
                    $fieldType->onCreate($subField, $content, $repository, $variantData );
                    $data[$field->getIdentifier()][$variant->getIdentifier()] = $variantData;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {

        // Case1: Old and new data has no variant identifier.
        if(empty($old_data[$field->getIdentifier()]['type']) && empty($data[$field->getIdentifier()]['type'])) {
            return;
        }

        // Case2: Old had no variant, but new has => onCreate
        if(empty($old_data[$field->getIdentifier()]['type']) && !empty($data[$field->getIdentifier()]['type'])) {
            $this->onCreate($field, $content, $repository, $data);
            return;
        }

        // Case3: Old has variant, but new has not => onSoftDelete
        if(!empty($old_data[$field->getIdentifier()]['type']) && empty($data[$field->getIdentifier()]['type'])) {
            $this->onSoftDelete($field, $content, $repository, $old_data);
            return;
        }

        // Case4 (A&B): variant identifier in old and new data available.
        if(!empty($old_data[$field->getIdentifier()]['type']) && !empty($data[$field->getIdentifier()]['type'])) {

            // Case 4A: Old had other variant than new data => onSoftDelete & onCreate
            if($old_data[$field->getIdentifier()]['type'] != $data[$field->getIdentifier()]['type']) {
                $this->onSoftDelete($field, $content, $repository, $old_data);
                $this->onCreate($field, $content, $repository, $data);
            }

            // Case 4B: Old has same variant as new.
            else {
                $variant = static::createVariant($content, $field, $data[$field->getIdentifier()]['type']);

                // If child field implements onUpdate, call it!
                foreach($variant->getFields() as $subField) {
                    $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

                    if(method_exists($fieldType, 'onUpdate')) {

                        $old_variant_data = $old_data[$field->getIdentifier()][$variant->getIdentifier()];
                        $new_variant_data = $data[$field->getIdentifier()][$variant->getIdentifier()];

                        $old_sub_data = $old_variant_data[$subField->getIdentifier()] ?? null;
                        $new_sub_data = $new_variant_data[$subField->getIdentifier()] ?? null;

                        if($old_sub_data || $new_sub_data) {
                            $fieldType->onUpdate($subField, $content, $repository, $old_variant_data, $new_variant_data );
                            $data[$field->getIdentifier()][$variant->getIdentifier()] = $new_variant_data;
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onSoftDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {

        // Only continue if we have data and a type for this field.
        if(empty($data[$field->getIdentifier()]) || empty($data[$field->getIdentifier()]['type']) || empty($data[$field->getIdentifier()][$data[$field->getIdentifier()]['type']])) {
            return;
        }

        $variant = static::createVariant($content, $field, $data[$field->getIdentifier()]['type'], $data[$field->getIdentifier()][$data[$field->getIdentifier()]['type']]);

        // If child field implements onCreate, call it!
        foreach($variant->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if(method_exists($fieldType, 'onSoftDelete')) {
                if(isset($variant->getData()[$subField->getIdentifier()])) {
                    $fieldType->onSoftDelete($subField, $content, $repository, $variant->getData() );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {

        // Only continue if we have data and a type for this field.
        if(empty($data[$field->getIdentifier()]) || empty($data[$field->getIdentifier()]['type']) || empty($data[$field->getIdentifier()][$data[$field->getIdentifier()]['type']])) {
            return;
        }

        $variant = static::createVariant($content, $field, $data[$field->getIdentifier()]['type'], $data[$field->getIdentifier()][$data[$field->getIdentifier()]['type']]);

        // If child field implements onCreate, call it!
        foreach($variant->getFields() as $subField) {
            $fieldType = $this->fieldTypeManager->getFieldType($subField->getType());

            if(method_exists($fieldType, 'onHardDelete')) {
                if(isset($variant->getData()[$subField->getIdentifier()])) {
                    $fieldType->onHardDelete($subField, $content, $repository, $variant->getData() );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);

        $settings['settings'] = $settings['settings'] ?? [];
        $settings['assets'] = [
            [ 'js' => 'main.js', 'package' => 'UniteCMSVariantsFieldBundle' ],
            [ 'css' => 'main.css', 'package' => 'UniteCMSVariantsFieldBundle' ],
        ];

        if($field) {
            $settings['settings']['variant_schema_types'] = [];
            $settings['settings']['variant_titles'] = [];
            $settings['settings']['on'] = $settings['settings']['on'] ?? [];

            // normalize settings for nested fields.
            $variants = self::getNestableFieldable($field);
            foreach($settings['settings']['on'] as $v => $fields) {
                $processor = new Processor();
                $variant = new Variant(null, $variants->getFieldsForVariant($v), $v, $v, $variants);
                $config = $processor->processConfiguration($this->tableViewConfigurationFactory->create($variant), ['settings' => ['fields' => $fields]]);
                $settings['settings']['on'][$v] = $config['fields'];
                $settings['settings']['variant_schema_types'][$v] = VariantFactory::schemaTypeNameForVariant($variant);

                // Template will only include assets from root fields, so we need to add any child templates to the root field.
                foreach($config['fields'] as $nestedField) {
                    if(!empty($nestedField['assets'])) {
                        $settings['assets'] = array_merge($settings['assets'], $nestedField['assets']);
                    }
                }
            }
        }

        foreach($field->getSettings()->variants as $variant) {
            $settings['settings']['variant_titles'][$variant['identifier']] = $variant['title'];
        }
    }
}
