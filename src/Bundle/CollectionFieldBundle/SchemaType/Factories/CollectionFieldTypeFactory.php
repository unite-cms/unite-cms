<?php

namespace UniteCMS\CollectionFieldBundle\SchemaType\Factories;

use UniteCMS\CoreBundle\Model\FieldableFieldContent;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CollectionFieldBundle\Model\Collection;
use UniteCMS\CollectionFieldBundle\Model\CollectionRow;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;

class CollectionFieldTypeFactory
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Creates a new collectionField schema type.
     *
     * @param SchemaTypeManager $schemaTypeManager
     * @param FieldableField $field
     * @param Collection $collection
     * @param boolean $isInputType
     * @return ObjectType
     */
    public function createCollectionFieldType(SchemaTypeManager $schemaTypeManager, FieldableField $field, Collection $collection, $isInputType = false)
    {
        $schemaTypeName = IdentifierNormalizer::graphQLType(str_replace('/', '', ucwords($collection->getIdentifierPath(), '/')), 'CollectionField');
        $schemaTypeRowName = $schemaTypeName . 'Row';

        if($isInputType) {
          $schemaTypeName .= 'Input';
          $schemaTypeRowName .= 'Input';
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

                    if(!$this->authorizationChecker->isGranted(FieldableFieldVoter::LIST, $field)) {
                        continue;
                    }

                    if($isInputType) {
                      $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLInputType($field, $schemaTypeManager);
                    } else {
                      $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType($field, $schemaTypeManager);
                    }

                    // field type can also return null, if no input / output is defined for this field.
                    if(!$fieldsSchemaTypes[$fieldIdentifier]) {
                        unset($fieldsSchemaTypes[$fieldIdentifier]);
                    }
                }

                if(empty($fieldsSchemaTypes)) {
                    return null;
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

                      $rootContent = $value['content'];
                      $value = $value['value'];

                      if(!isset($value[$info->fieldName]) && isset($fields[$info->fieldName]->getSettings()->default)) {
                        return $fields[$info->fieldName]->getSettings()->default;
                      }

                      if(!isset($fieldTypes[$info->fieldName]) || !isset($fields[$info->fieldName]) || !isset($value[$info->fieldName])) {
                        return null;
                      }

                      $collectionRow = new CollectionRow($collection, $value, $rootContent, $info->path[count($info->path) - 2]);

                      if(!$this->authorizationChecker->isGranted(FieldableFieldVoter::VIEW, new FieldableFieldContent($fields[$info->fieldName], $collectionRow))) {
                          return null;
                      }

                      $return_value = null;
                      $fieldType = $this->fieldTypeManager->getFieldType($fieldTypes[$info->fieldName]->getType());
                      $return_value = $fieldType->resolveGraphQLData(
                          $fields[$info->fieldName],
                          $value[$info->fieldName],
                          $collectionRow,
                          $args,
                          $context,
                          $info
                      );
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
