<?php

namespace UnitedCMS\CoreBundle\SchemaType\Factories;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Field\FieldType;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentTypeFactory implements SchemaTypeFactoryInterface
{

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    public function __construct(FieldTypeManager $fieldTypeManager, EntityManager $entityManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Returns true, if this factory can create a schema for the given name.
     *
     * @param string $schemaTypeName
     * @return bool
     */
    public function supports(string $schemaTypeName): bool
    {
        $nameParts = preg_split('/(?=[A-Z])/', $schemaTypeName, -1, PREG_SPLIT_NO_EMPTY);

        // If this has an Level Suffix, we need to remove it first.
        if(substr($nameParts[count($nameParts) - 1], 0, strlen('Level')) == 'Level') {
            array_pop($nameParts);
        }

        // Support for content type.
        if(count($nameParts) == 2) {
            if($nameParts[1] == 'Content') {
                return true;
            }
        }

        // Support for content input type.
        if(count($nameParts) == 3) {
            if($nameParts[1] == 'Content' && $nameParts[2] == 'Input') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the new created schema type object for the given name.
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @param Domain $domain
     * @param string $schemaTypeName
     * @return Type
     */
    public function createSchemaType(SchemaTypeManager $schemaTypeManager, int $nestingLevel, Domain $domain = null, string $schemaTypeName): Type
    {
        if(!$domain) {
            throw new \InvalidArgumentException('UnitedCMS\CoreBundle\SchemaType\Factories\ContentTypeFactory::createSchemaType needs an domain as second argument');
        }

        $nameParts = preg_split('/(?=[A-Z])/', $schemaTypeName, -1, PREG_SPLIT_NO_EMPTY);

        $identifier = strtolower($nameParts[0]);

        $isInputType = (count($nameParts) == 3 && $nameParts[2] == 'Input');

        /**
         * @var ContentType $contentType
         */
        if (!$contentType = $domain->getContentTypes()->get($identifier)) {
            throw new \InvalidArgumentException(
                "No contentType with identifier '$identifier' found for in the given domain."
            );
        }

        // Load the full contentType if it is not already loaded.
        if(!$this->entityManager->contains($contentType)) {
            $contentType = $this->entityManager->getRepository('UnitedCMSCoreBundle:ContentType')->find(
                $contentType->getId()
            );
        }

        /**
         * @var Type[] $fields
         */
        $fields = [];

        /**
         * @var FieldType[] $fieldTypes
         */
        $fieldTypes = [];

        /**
         * @var \UnitedCMS\CoreBundle\Entity\ContentTypeField $field
         */
        foreach ($contentType->getFields() as $field) {
            $fieldTypes[$field->getIdentifier()] = $this->fieldTypeManager->getFieldType($field->getType());

            // If we want to create an InputObjectType, get GraphQLInputType.
            if($isInputType) {
                $fields[$field->getIdentifier()] = $fieldTypes[$field->getIdentifier()]->getGraphQLInputType($field, $schemaTypeManager, $nestingLevel + 1);
            } else {
                $fields[$field->getIdentifier()] = $fieldTypes[$field->getIdentifier()]->getGraphQLType($field, $schemaTypeManager, $nestingLevel + 1);
            }
        }

        if($isInputType) {

            if(count($contentType->getLocales()) > 0) {
                $fields = array_merge([
                    'locale' => Type::nonNull(Type::string())
                ], $fields);
            }

            return new InputObjectType(
                [
                    'name' => ucfirst($identifier) . 'ContentInput' . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''),
                    'fields' => $fields,
                ]
            );
        } else {
            return new ObjectType(
                [
                    'name' => ucfirst($identifier) . 'Content' . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''),
                    'fields' => array_merge(
                        [
                            'id' => Type::id(),
                            'type' => Type::string(),
                            'created' => Type::int(),
                            'updated' => Type::int(),
                            'deleted' => Type::int(),
                        ],
                        $fields
                    ),
                    'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use (
                        $contentType,
                        $fieldTypes
                    ) {

                        if (!$value instanceof Content) {
                            throw new \InvalidArgumentException(
                                'Value must be instance of ' . Content::class . '.'
                            );
                        }

                        switch ($info->fieldName) {
                            case 'id':
                                return $value->getId();
                            case 'type':
                                return $value->getContentType()->getIdentifier();
                            case 'created':
                                return $value->getCreated()->getTimestamp();
                            case 'updated':
                                return $value->getUpdated()->getTimestamp();
                            case 'deleted':
                                return $value->getDeleted() ? $value->getDeleted()->getTimestamp() : null;
                            default:

                                if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                    return null;
                                }

                                $fieldData = array_key_exists(
                                    $info->fieldName,
                                    $value->getData()
                                ) ? $value->getData()[$info->fieldName] : null;
                                $data = $fieldTypes[$info->fieldName]->resolveGraphQLData($contentType->getFields()->get($info->fieldName), $fieldData);

                                return $data;
                        }
                    },
                    'interfaces' => [$schemaTypeManager->getSchemaType('ContentInterface')],
                ]
            );
        }
    }
}