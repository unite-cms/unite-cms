<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentTypeTranslationsFactory implements SchemaTypeFactoryInterface
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(
        EntityManager $entityManager,
        AuthorizationChecker $authorizationChecker,
        FieldTypeManager $fieldTypeManager
    ) {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Returns true, if this factory can create a schema for the given name.
     *
     * @param string $schemaTypeName
     * @return bool
     */
    public function supports(string $schemaTypeName): bool
    {
        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);

        // Support for content type.
        if (count($nameParts) == 3) {
            if ($nameParts[1] == 'Content' && $nameParts[2] == 'Translations') {
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
    public function createSchemaType(
        SchemaTypeManager $schemaTypeManager,
        int $nestingLevel,
        Domain $domain = null,
        string $schemaTypeName
    ): Type {
        if (!$domain) {
            throw new \InvalidArgumentException(
                'UniteCMS\CoreBundle\SchemaType\Factories\ContentTypeTranslationsFactory::createSchemaType needs an domain as second argument'
            );
        }

        $identifier = IdentifierNormalizer::fromGraphQLSchema($schemaTypeName);

        /**
         * @var ContentType $contentType
         */
        if (!$contentType = $domain->getContentTypes()->get($identifier)) {
            throw new \InvalidArgumentException(
                "No contentType with identifier '$identifier' found for in the given domain."
            );
        }

        // Load the full contentType if it is not already loaded.
        if (!$this->entityManager->contains($contentType)) {
            $contentType = $this->entityManager->getRepository('UniteCMSCoreBundle:ContentType')->find(
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
         * @var \UniteCMS\CoreBundle\Entity\ContentTypeField $field
         */
        foreach ($contentType->getFields() as $field) {

            $fieldIdentifier = IdentifierNormalizer::graphQLIdentifier($field);

            try {
                $fieldTypes[$fieldIdentifier] = $this->fieldTypeManager->getFieldType($field->getType());

                $fields[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType(
                    $field,
                    $schemaTypeManager,
                    $nestingLevel + 1
                );

                // During schema creation, a field can throw an access denied exception. If this happens, we just skip this field.
            } catch (AccessDeniedException $accessDeniedException) {
                // TODO: We should log this here and show it to the user somewhere.

                // During schema creation, a field can throw an invalid field configuration exception. If this happens, we just skip this field.
            } catch (InvalidFieldConfigurationException $accessDeniedException) {
                // TODO: We should log this here and show it to the user somewhere.
            }
        }

        return new ObjectType(
            [
                'name' => IdentifierNormalizer::graphQLType(
                        $identifier,
                        'ContentTranslations'
                    ).($nestingLevel > 0 ? 'Level'.$nestingLevel : ''),
                'args' => [
                    'locales' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'List of Locales for Example: all translations ($locales = null), one locale ($locales = ["de"]), or multiple ($locales = ["de", "en"]',
                        'defaultValue' => null,
                    ],
                ],
                'fields' => array_merge(
                    [
                        'id' => Type::id(),
                        'type' => Type::string(),
                    ],
                    empty($contentType->getLocales()) ? [] : [
                        'locale' => Type::string(),
                    ],
                    [
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
                    if (!empty($value) && $value instanceof Content) {

                        switch ($info->fieldName) {
                            case 'id':
                                return $value->getId();
                            case 'type':
                                return $value->getContentType()->getIdentifier();
                            case 'locale':
                                return $value->getLocale();
                            case 'created':
                                return $value->getCreated() ? $value->getCreated()->getTimestamp() : null;
                            case 'updated':
                                return $value->getUpdated() ? $value->getUpdated()->getTimestamp() : null;
                            case 'deleted':
                                return $value->getDeleted() ? $value->getDeleted()->getTimestamp() : null;
                            default:
                                if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                    return null;
                                }

                                $fieldData = array_key_exists($info->fieldName, $value->getData()) ? $value->getData(
                                )[$info->fieldName] : null;

                                return $fieldTypes[$info->fieldName]->resolveGraphQLData(
                                    $contentType->getFields()->get($info->fieldName),
                                    $fieldData
                                );
                        }
                    }

                    return null;
                },
                'interfaces' => [$schemaTypeManager->getSchemaType('ContentInterface')],
            ]
        );
    }
}
