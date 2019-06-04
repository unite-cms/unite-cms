<?php

namespace UniteCMS\CollectionFieldBundle\SchemaType\Factories;

use UniteCMS\CollectionFieldBundle\Field\Types\CollectionFieldType;
use UniteCMS\CoreBundle\Entity\Domain;
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
use UniteCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;

class CollectionFieldTypeFactory implements SchemaTypeFactoryInterface
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
     * {@inheritDoc}
     */
    public function supports(string $schemaTypeName): bool
    {
        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);
        if(count($nameParts) < 4) {
            return false;
        }

        $last = array_pop($nameParts);

        if($last == 'Input') {
            $last = array_pop($nameParts);
        }

        if($last !== 'Row') {
            return false;
        }

        $last = array_pop($nameParts);

        if($last !== 'Field') {
            return false;
        }

        $last = array_pop($nameParts);

        if($last !== 'Collection') {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function createSchemaType(SchemaTypeManager $schemaTypeManager, Domain $domain = null, string $schemaTypeName): Type {

        if(!$domain) {
            throw new \InvalidArgumentException('UniteCMS\CollectionFieldBundle\SchemaType\Factories\CollectionFieldTypeFactory::createSchemaType needs an domain as second argument');
        }

        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);
        $fieldable = null;
        $fieldableName = strtolower(array_shift($nameParts));

        $isInput = array_pop($nameParts) === 'Input';

        // Check if content type exists.
        $fieldable = $domain->getContentTypes()->get($fieldableName);

        if(empty($fieldable)) {
            $fieldable = $domain->getSettingTypes()->get($fieldableName);
        }

        if(empty($fieldable)) {
            $fieldable = $domain->getDomainMemberTypes()->get($fieldableName);
        }

        if(empty($fieldable)) {
            throw new \InvalidArgumentException(sprintf('No fieldable "%s" found in domain "%s".', $fieldableName, $domain->getIdentifier()));
        }

        $fieldName = strtolower(array_shift($nameParts));
        $field = $fieldable->getFields()->filter(function(FieldableField $field) use ($fieldName) {
            return $field->getIdentifier() === $fieldName && $field->getType() === CollectionFieldType::getType();
        })->first();

        if(empty($field)) {
            throw new \InvalidArgumentException(sprintf('No field "%s" of type "%s" found in fieldable "%s".', $fieldName, CollectionFieldType::getType(), $fieldable->getIdentifier()));
        }

        return $this->createCollectionRowType($schemaTypeManager, CollectionFieldType::getNestableFieldable($field), $isInput, $schemaTypeName);
    }

    /**
     * @param SchemaTypeManager $schemaTypeManager
     * @param Collection $collection
     * @param bool $isInputType
     * @param string $schemaTypeRowName
     * @return Type
     */
    protected function createCollectionRowType(SchemaTypeManager $schemaTypeManager, Collection $collection, bool $isInputType, string $schemaTypeRowName) : Type {
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
            foreach ($collection->getFields() as $field) {
                $fieldIdentifier = IdentifierNormalizer::graphQLIdentifier($field);
                $fields[$fieldIdentifier] = $field;
                $fieldTypes[$fieldIdentifier] = $this->fieldTypeManager->getFieldType($field->getType());

                if (!$this->authorizationChecker->isGranted(FieldableFieldVoter::LIST, $field)) {
                    continue;
                }

                if ($isInputType) {
                    $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLInputType(
                        $field,
                        $schemaTypeManager
                    );
                } else {
                    $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType(
                        $field,
                        $schemaTypeManager
                    );
                }

                // field type can also return null, if no input / output is defined for this field.
                if (!$fieldsSchemaTypes[$fieldIdentifier]) {
                    unset($fieldsSchemaTypes[$fieldIdentifier]);
                }
            }

            if (empty($fieldsSchemaTypes)) {
                return null;
            }

            if ($isInputType) {
                $schemaTypeManager->registerSchemaType(
                    new InputObjectType(
                        [
                            'name' => $schemaTypeRowName,
                            'fields' => function () use ($fieldsSchemaTypes) {
                                return $fieldsSchemaTypes;
                            }
                        ]
                    )
                );
            } else {
                $schemaTypeManager->registerSchemaType(
                    new ObjectType(
                        [
                            'name' => $schemaTypeRowName,
                            'fields' => function () use ($fieldsSchemaTypes) {
                                return $fieldsSchemaTypes;
                            },
                            'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use (
                                $collection,
                                $fields,
                                $fieldTypes
                            ) {

                                $rootContent = $value['content'];
                                $value = $value['value'];

                                if (!isset($value[$info->fieldName]) && isset(
                                        $fields[$info->fieldName]->getSettings()->default
                                    )) {
                                    return $fields[$info->fieldName]->getSettings()->default;
                                }

                                if (!isset($fieldTypes[$info->fieldName]) || !isset($fields[$info->fieldName]) || !isset($value[$info->fieldName])) {
                                    return null;
                                }

                                $collectionRow = new CollectionRow(
                                    $collection,
                                    $value,
                                    $rootContent,
                                    $info->path[count($info->path) - 2]
                                );

                                if (!$this->authorizationChecker->isGranted(
                                    FieldableFieldVoter::VIEW,
                                    new FieldableFieldContent($fields[$info->fieldName], $collectionRow)
                                )) {
                                    return null;
                                }

                                $return_value = null;
                                $fieldType = $this->fieldTypeManager->getFieldType(
                                    $fieldTypes[$info->fieldName]->getType()
                                );
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
                        ]
                    )
                );
            }
        }
        return $schemaTypeManager->getSchemaType($schemaTypeRowName);
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
            $newSchemaType = new ListOfType($this->createCollectionRowType($schemaTypeManager, $collection, $isInputType, $schemaTypeRowName));
            $newSchemaType->name = $schemaTypeName;
            $schemaTypeManager->registerSchemaType($newSchemaType);
        }

        return $schemaTypeManager->getSchemaType($schemaTypeName);
    }
}
