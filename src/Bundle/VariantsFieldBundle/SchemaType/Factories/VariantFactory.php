<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 06.08.18
 * Time: 09:22
 */

namespace UniteCMS\VariantsFieldBundle\SchemaType\Factories;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\VariantsFieldBundle\Model\Variant;

class VariantFactory
{
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Returns the full object name of a variant that is resolvable via schema type manager.
     *
     * @param Variant $variant
     * @return string
     */
    static function schemaTypeNameForVariant(Variant $variant) {
        $identifierParts = explode('/', $variant->getIdentifierPath('/'));
        $identifierName = ucfirst(array_shift($identifierParts));
        $identifierName .= ($variant->getRootEntity() instanceof ContentType ? 'Content' : 'Setting');

        foreach($identifierParts as $part) {
            $identifierName .= ucfirst($part);
        }

        $identifierName .= 'Variant';
        return $identifierName;
    }

    public function createVariantType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0, Variant $variant) : Type {

        $schemaTypeName = self::schemaTypeNameForVariant($variant);

        if(!$schemaTypeManager->hasSchemaType($schemaTypeName)) {

            /**
             * @var FieldableField[] $fields
             */
            $fields = [];

            /**
             * @var Type[] $fieldsSchemaTypes
             */
            $fieldsSchemaTypes = [];

            /**
             * @var FieldTypeInterface[] $fieldTypes
             */
            $fieldTypes = [];
            foreach($variant->getFields() as $field) {
                $fieldIdentifier = substr(IdentifierNormalizer::graphQLIdentifier($field), strlen($variant->getIdentifier()) + 1);
                $fields[$fieldIdentifier] = $field;
                $fieldTypes[$fieldIdentifier] = $this->fieldTypeManager->getFieldType($field->getType());
                $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType($field, $schemaTypeManager, $nestingLevel);
            }

            $schemaTypeManager->registerSchemaType(new ObjectType([
                'name' => $schemaTypeName,
                'fields' => array_merge(
                    [
                        'type' => Type::string(),
                    ],
                    $fieldsSchemaTypes
                ),
                'interfaces' =>[ $schemaTypeManager->getSchemaType('VariantsFieldInterface') ],
                'resolveField' => function($value, array $args, $context, ResolveInfo $info) use ($fields, $fieldTypes) {

                    if(!$value instanceof Variant) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.Variant::class.'.'
                        );
                    }

                    if($info->fieldName === 'type') {
                        return $value->getIdentifier();
                    }

                    $normalizedFieldName = str_replace('_', '-', $info->fieldName);

                    if(!isset($fieldTypes[$info->fieldName]) || !isset($fields[$info->fieldName]) || !isset($value->getData()[$normalizedFieldName])) {
                        return null;
                    }

                    $return_value = null;
                    $fieldType = $this->fieldTypeManager->getFieldType($fieldTypes[$info->fieldName]->getType());
                    $return_value = $fieldType->resolveGraphQLData($fields[$info->fieldName], $value->getData()[$normalizedFieldName]);
                    return $return_value;
                }
            ]));
        }
        return $schemaTypeManager->getSchemaType($schemaTypeName);
    }
}