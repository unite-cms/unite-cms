<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Exception\AccessDeniedException;
use UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\SchemaType\Types\PermissionsType;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

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

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    public function __construct(FieldTypeManager $fieldTypeManager, EntityManager $entityManager, AuthorizationChecker $authorizationChecker)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
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
        if (count($nameParts) == 2) {
            if ($nameParts[1] == 'Content') {
                return true;
            }
        }

        // Support for content input type.
        if (count($nameParts) == 3) {
            if ($nameParts[1] == 'Content' && $nameParts[2] == 'Input') {
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
                'UniteCMS\CoreBundle\SchemaType\Factories\ContentTypeFactory::createSchemaType needs an domain as second argument'
            );
        }

        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);
        $identifier = IdentifierNormalizer::fromGraphQLSchema($schemaTypeName);
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

                // If we want to create an InputObjectType, get GraphQLInputType.
                if ($isInputType) {
                    $fields[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLInputType(
                        $field,
                        $schemaTypeManager,
                        $nestingLevel + 1
                    );
                } else {
                    $fields[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType(
                        $field,
                        $schemaTypeManager,
                        $nestingLevel + 1
                    );
                }

                // field type can also return null, if no input / output is defined for this field.
                if(!$fields[$fieldIdentifier]) {
                    unset($fields[$fieldIdentifier]);
                }

                // During schema creation, a field can throw an access denied exception. If this happens, we just skip this field.
            } catch (AccessDeniedException $accessDeniedException) {
                // TODO: We should log this here and show it to the user somewhere.

                // During schema creation, a field can throw an invalid field configuration exception. If this happens, we just skip this field.
            } catch (InvalidFieldConfigurationException $accessDeniedException) {
                // TODO: We should log this here and show it to the user somewhere.
            }
        }

        // Create or get permissions type for this content type.
        $permissionsTypeName = IdentifierNormalizer::graphQLType($identifier, 'ContentPermissions');
        if(!$schemaTypeManager->hasSchemaType($permissionsTypeName)) {
            $schemaTypeManager->registerSchemaType(new PermissionsType(ContentVoter::ENTITY_PERMISSIONS, $permissionsTypeName));
        }

        if ($isInputType) {
            return new InputObjectType(
                [
                    'name' => IdentifierNormalizer::graphQLType(
                            $identifier,
                            'ContentInput'
                        ).($nestingLevel > 0 ? 'Level'.$nestingLevel : ''),
                    'fields' => $fields,
                ]
            );
        } else {

            return new ObjectType(
                [
                    'name' => IdentifierNormalizer::graphQLType(
                            $identifier,
                            'Content'
                        ).($nestingLevel > 0 ? 'Level'.$nestingLevel : ''),
                    'fields' => array_merge(
                        [
                            'id' => Type::id(),
                            'type' => Type::string(),
                            '_permissions' => $schemaTypeManager->getSchemaType($permissionsTypeName, $domain),
                        ],
                        empty($contentType->getLocales()) ? [] : [
                            'locale' => Type::string(),
                            'translations' => [
                                'type' => Type::listOf(
                                    $schemaTypeManager->getSchemaType(
                                        IdentifierNormalizer::graphQLType($identifier, 'Content').'Level'.($nestingLevel + 1),
                                        $domain,
                                        $nestingLevel + 1
                                    )
                                ),
                                'args' => [
                                    'locales' => [
                                        'type' => Type::listOf(Type::string()),
                                        'description' => 'List of Locales for Example: all translations ($locales = null), one locale ($locales = ["de"]), or multiple ($locales = ["de", "en"]',
                                        'defaultValue' => null,
                                    ],
                                ],
                            ],
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

                        if (!$value instanceof Content) {
                            throw new \InvalidArgumentException(
                                'Value must be instance of '.Content::class.'.'
                            );
                        }

                        switch ($info->fieldName) {
                            case 'id':
                                return $value->getId();
                            case 'type':
                                return $value->getContentType()->getIdentifier();
                            case '_permissions':
                                $permissions = [];
                                foreach(ContentVoter::ENTITY_PERMISSIONS as $permission) {
                                    $permissions[$permission] = $this->authorizationChecker->isGranted($permission, $value);
                                }
                                return $permissions;

                            case 'locale':
                                return $value->getLocale();
                            case 'created':
                                return $value->getCreated() ? $value->getCreated()->getTimestamp() : null;
                            case 'updated':
                                return $value->getUpdated() ? $value->getUpdated()->getTimestamp() : null;
                            case 'deleted':
                                return $value->getDeleted() ? $value->getDeleted()->getTimestamp() : null;
                            case 'translations':

                                $translations = [];
                                $includeLocales = $args['locales'] ?? $value->getContentType()->getLocales();
                                $includeLocales = is_string($includeLocales) ? [$includeLocales] : $includeLocales;
                                $includeLocales = array_diff($includeLocales, [$value->getLocale()]);

                                // Case 1: This is the base translation
                                if(empty($value->getTranslationOf())) {
                                    foreach($value->getTranslations() as $translation) {
                                        if(in_array($translation->getLocale(), $includeLocales)) {
                                            $translations[$translation->getLocale()] = $translation;
                                        }
                                    }
                                }
                                // Case 2: This is a translation of a base translation
                                else {
                                    if(in_array($value->getTranslationOf()->getLocale(), $includeLocales)) {
                                        $translations[$value->getTranslationOf()->getLocale()] = $value->getTranslationOf();
                                    }
                                    foreach($value->getTranslationOf()->getTranslations() as $translation) {
                                        if(in_array($translation->getLocale(), $includeLocales)) {
                                            $translations[$translation->getLocale()] = $translation;
                                        }
                                    }
                                }

                                // Remove all translations, we don't have access to.
                                foreach($translations as $locale => $translation) {
                                    if(!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $translation)) {
                                        unset($translations[$locale]);
                                    }
                                }

                                return $translations;

                            default:

                                if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                    return null;
                                }

                                $fieldData = array_key_exists($info->fieldName, $value->getData()) ? $value->getData(
                                )[$info->fieldName] : null;

                                return $fieldTypes[$info->fieldName]->resolveGraphQLData(
                                    $contentType->getFields()->get($info->fieldName),
                                    $fieldData,
                                    $value
                                );
                        }
                    },
                    'interfaces' => [$schemaTypeManager->getSchemaType('ContentInterface')],
                ]
            );
        }
    }
}
