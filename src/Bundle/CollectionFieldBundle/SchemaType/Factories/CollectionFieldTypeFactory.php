<?php

namespace UniteCMS\CollectionFieldBundle\SchemaType\Factories;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CollectionFieldBundle\Model\Collection;
use UniteCMS\CollectionFieldBundle\Model\CollectionRow;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class CollectionFieldTypeFactory
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Creates a new collectionField schema type.
     *
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @param FieldableField $field
     * @param Collection $collection
     * @param boolean $isInputType
     * @return ObjectType
     */
    public function createCollectionFieldType(SchemaTypeManager $schemaTypeManager, int $nestingLevel, FieldableField $field, Collection $collection, $isInputType = false)
    {
        $schemaTypeName = IdentifierNormalizer::graphQLType(str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')), 'CollectionField');
        $schemaTypeRowName = $schemaTypeName . 'Row';

        if($isInputType) {
          $schemaTypeName .= 'Input';
          $schemaTypeRowName .= 'Input';
        }

        else {
            if ($nestingLevel > 0) {
                $schemaTypeName .= 'Level'.$nestingLevel;
                $schemaTypeRowName .= 'Level'.$nestingLevel;
            }
        }

        if(!$schemaTypeManager->hasSchemaType($schemaTypeName)) {
            if(!$schemaTypeManager->hasSchemaType($schemaTypeRowName)) {

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
                foreach($collection->getFields() as $field) {
                    $fieldIdentifier = IdentifierNormalizer::graphQLIdentifier($field);
                    $fields[$fieldIdentifier] = $field;
                    $fieldTypes[$fieldIdentifier] = $this->fieldTypeManager->getFieldType($field->getType());

                    if($isInputType) {
                      $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLInputType($field, $schemaTypeManager, $nestingLevel + 1);
                    } else {
                      $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType($field, $schemaTypeManager, $nestingLevel + 1);
                    }
                }

                if($isInputType) {
                  $schemaTypeManager->registerSchemaType(new InputObjectType([
                    'name' => $schemaTypeRowName,
                    'fields' => function() use($fieldsSchemaTypes){
                      return $fieldsSchemaTypes;
                    }
                  ]));
                } else {
                  $schemaTypeManager->registerSchemaType(new ObjectType([
                    'name' => $schemaTypeRowName,
                    'fields' => function() use($fieldsSchemaTypes){
                      return $fieldsSchemaTypes;
                    },
                    'resolveField' => function($value, array $args, $context, ResolveInfo $info) use ($collection, $fields, $fieldTypes) {

                      if(!isset($fieldTypes[$info->fieldName]) || !isset($fields[$info->fieldName]) || !isset($value[$info->fieldName])) {
                        return null;
                      }

                      $return_value = null;
                      $fieldType = $this->fieldTypeManager->getFieldType($fieldTypes[$info->fieldName]->getType());
                      $return_value = $fieldType->resolveGraphQLData($fields[$info->fieldName], $value[$info->fieldName], new CollectionRow($collection, $value));
                      return $return_value;
                    }
                  ]));
                }
            }
            $newSchemaType = new ListOfType($schemaTypeManager->getSchemaType($schemaTypeRowName));
            $newSchemaType->name = $schemaTypeName;
            $schemaTypeManager->registerSchemaType($newSchemaType);
        }

        return $schemaTypeManager->getSchemaType($schemaTypeName);
    }

}
