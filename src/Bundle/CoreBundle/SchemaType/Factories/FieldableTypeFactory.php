<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use UniteCMS\CoreBundle\Model\FieldableFieldContent;
use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Exception\AccessDeniedException;
use UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\SchemaType\Types\PermissionsType;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;

class FieldableTypeFactory implements SchemaTypeFactoryInterface
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
     * Returns a list of GraphQL schema type names that are supported by this factory.
     */
    public function getSupportedEntities() : array {

        $contentType = new ContentType();
        $settingType = new SettingType();
        $domainMemberType = new DomainMemberType();

        $names = [
            IdentifierNormalizer::graphQLType($contentType),
            IdentifierNormalizer::graphQLType($settingType),
            IdentifierNormalizer::graphQLType($domainMemberType),
        ];

        unset($contentType);
        unset($settingType);
        unset($domainMemberType);
        return $names;
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

        // Support for fieldable types.
        if (count($nameParts) == 2) {
            if (in_array($nameParts[1], $this->getSupportedEntities())) {
                return true;
            }
        }

        // Support for fieldable input types.
        if (count($nameParts) == 3) {
            if (in_array($nameParts[1], $this->getSupportedEntities()) && $nameParts[2] == 'Input') {
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
                'UniteCMS\CoreBundle\SchemaType\Factories\FieldableTypeFactory::createSchemaType needs an domain as second argument'
            );
        }

        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);
        $identifier = IdentifierNormalizer::fromGraphQLSchema($schemaTypeName);
        $isInputType = (count($nameParts) == 3 && $nameParts[2] == 'Input');

        $entityType = $nameParts[1];
        $entityPermissions = [];

        /**
         * @var Fieldable $fieldable
         */
        $fieldable = null;

        if(!in_array($entityType, $this->getSupportedEntities())) {
            throw new \InvalidArgumentException(
                "Invalid entity type '$entityType'."
            );
        }

        if($entityType === 'Content' && $domain->getContentTypes()->get($identifier)) {
            $fieldable = $domain->getContentTypes()->get($identifier);
            $entityPermissions = ContentVoter::ENTITY_PERMISSIONS;
        }

        else if($entityType === 'Setting' && $domain->getSettingTypes()->get($identifier)) {
            $fieldable = $domain->getSettingTypes()->get($identifier);
            $entityPermissions = SettingVoter::ENTITY_PERMISSIONS;
        }

        else if($entityType === 'Member' && $domain->getDomainMemberTypes()->get($identifier)) {
            $fieldable = $domain->getDomainMemberTypes()->get($identifier);
            $entityPermissions = DomainMemberVoter::ENTITY_PERMISSIONS;
        }

        if (!$fieldable) {
            throw new \InvalidArgumentException(
                "No '$entityType' type with identifier '$identifier' found for in the given domain."
            );
        }

        // Load the full entity if it is not already loaded.
        if (!$this->entityManager->contains($fieldable) && !empty($fieldable->getId())) {
            $fieldable = $this->entityManager->getRepository(get_class($fieldable))->find(
                $fieldable->getId()
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
         * @var FieldableField $field
         */
        foreach ($fieldable->getFields() as $field) {

            $fieldIdentifier = IdentifierNormalizer::graphQLIdentifier($field);

            if(!$this->authorizationChecker->isGranted(FieldableFieldVoter::LIST, $field)) {
                continue;
            }

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

        // Create or get permissions type for this fieldable type.
        $permissionsTypeName = IdentifierNormalizer::graphQLType($identifier, $entityType . 'Permissions');
        if(!$schemaTypeManager->hasSchemaType($permissionsTypeName)) {
            $schemaTypeManager->registerSchemaType(new PermissionsType($entityPermissions, $permissionsTypeName));
        }

        $name = $isInputType ?
            IdentifierNormalizer::graphQLType($identifier, $entityType . 'Input').($nestingLevel > 0 ? 'Level'.$nestingLevel : '') :
            IdentifierNormalizer::graphQLType($identifier, $entityType).($nestingLevel > 0 ? 'Level'.$nestingLevel : '');

        if($schemaTypeManager->hasSchemaType($name)) {
            return $schemaTypeManager->getSchemaType($name, $domain, ($nestingLevel + 1));
        }

        if ($isInputType) {
            return new InputObjectType(
                [
                    'name' => $name,
                    'fields' => $fields,
                ]
            );
        } else {

            return new ObjectType(
                [
                    'name' => $name,
                    'fields' => array_merge(
                        [
                            'id' => Type::id(),
                            'type' => Type::string(),
                            '_permissions' => $schemaTypeManager->getSchemaType($permissionsTypeName, $domain),
                        ],
                        empty($fieldable->getLocales()) ? [] : [
                            'locale' => Type::string(),
                            'translations' => [
                                'type' => Type::listOf(
                                    $schemaTypeManager->getSchemaType(
                                        IdentifierNormalizer::graphQLType($identifier, $entityType).'Level'.($nestingLevel + 1),
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
                        ],
                        ($fieldable instanceof ContentType) ? [
                            'deleted' => Type::int(),
                        ] : [],
                        ($fieldable instanceof DomainMemberType) ? [
                            '_name' => Type::string(),
                        ] : [],
                        $fields
                    ),
                    'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use (
                        $fieldable,
                        $entityPermissions,
                        $fieldTypes
                    ) {

                        if (!$value instanceof FieldableContent) {
                            throw new \InvalidArgumentException(
                                'Value must be instance of '.FieldableContent::class.'.'
                            );
                        }

                        switch ($info->fieldName) {
                            case 'id':
                                return $value->getId();
                            case 'type':
                                return $value->getEntity()->getIdentifier();
                            case '_permissions':
                                $permissions = [];
                                foreach($entityPermissions as $permission) {
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
                                $includeLocales = $args['locales'] ?? $value->getEntity()->getLocales();
                                $includeLocales = is_string($includeLocales) ? [$includeLocales] : $includeLocales;

                                if($value instanceof Content) {

                                    // Case 1: This is the base translation
                                    if (empty($value->getTranslationOf())) {
                                        foreach ($value->getTranslations() as $translation) {
                                            if (in_array($translation->getLocale(), $includeLocales)) {
                                                $translations[$translation->getLocale()] = $translation;
                                            }
                                        }
                                    }

                                    // Case 2: This is a translation of a base translation
                                    else {
                                        if (in_array($value->getTranslationOf()->getLocale(), $includeLocales)) {
                                            $translations[$value->getTranslationOf()->getLocale(
                                            )] = $value->getTranslationOf();
                                        }
                                        foreach ($value->getTranslationOf()->getTranslations() as $translation) {
                                            if (in_array($translation->getLocale(), $includeLocales)) {
                                                $translations[$translation->getLocale()] = $translation;
                                            }
                                        }
                                    }

                                    // Remove all translations, we don't have access to.
                                    foreach ($translations as $locale => $translation) {
                                        if (!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $translation)) {
                                            unset($translations[$locale]);
                                        }
                                    }
                                }

                                elseif($value instanceof Setting) {
                                    foreach ($value->getSettingType()->getLocales() as $locale) {
                                        if(in_array($locale, $includeLocales)) {
                                            $translations[] = $value->getSettingType()->getSetting($locale);
                                        }
                                    }

                                    // Remove all translations, we don't have access to.
                                    foreach($translations as $locale => $translation) {
                                        if(!$this->authorizationChecker->isGranted(SettingVoter::VIEW, $translation)) {
                                            unset($translations[$locale]);
                                        }
                                    }
                                }

                                return $translations;

                            case '_name':
                                return $value instanceof DomainMember ? (string)$value : null;

                            default:

                                if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                    return null;
                                }

                                if(!$this->authorizationChecker->isGranted(FieldableFieldVoter::VIEW, new FieldableFieldContent($fieldable->getFields()->get($info->fieldName), $value))) {
                                    return null;
                                }

                                $fieldData = array_key_exists($info->fieldName, $value->getData()) ? $value->getData(
                                )[$info->fieldName] : null;

                                return $fieldTypes[$info->fieldName]->resolveGraphQLData(
                                    $fieldable->getFields()->get($info->fieldName),
                                    $fieldData,
                                    $value,
                                    $args,
                                    $context,
                                    $info
                                );
                        }
                    },
                    'interfaces' => [$schemaTypeManager->getSchemaType('FieldableContentInterface')],
                ]
            );
        }
    }
}
