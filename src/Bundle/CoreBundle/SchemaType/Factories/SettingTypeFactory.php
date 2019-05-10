<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Exception\AccessDeniedException;
use UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\SchemaType\Types\PermissionsType;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;

class SettingTypeFactory implements SchemaTypeFactoryInterface
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

        if (count($nameParts) !== 2) {
            return false;
        }

        if ($nameParts[1] !== 'Setting') {
            return false;
        }

        return true;
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
                'UniteCMS\CoreBundle\SchemaType\Factories\SettingTypeFactory::createSchemaType needs an domain as second argument'
            );
        }

        $identifier = IdentifierNormalizer::fromGraphQLSchema($schemaTypeName);

        /**
         * @var SettingType $settingType
         */
        if (!$settingType = $domain->getSettingTypes()->get($identifier)) {
            throw new \InvalidArgumentException(
                "No settingType with identifier '$identifier' found for in the given domain."
            );
        }

        // Load the full settingType if it is not already loaded.
        if (!$this->entityManager->contains($settingType)) {
            $settingType = $this->entityManager->getRepository('UniteCMSCoreBundle:SettingType')->find(
                $settingType->getId()
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
         * @var \UniteCMS\CoreBundle\Entity\SettingTypeField $field
         */
        foreach ($settingType->getFields() as $field) {
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

        // Create or get permissions type for this content type.
        $permissionsTypeName = IdentifierNormalizer::graphQLType($identifier, 'SettingPermissions');
        if(!$schemaTypeManager->hasSchemaType($permissionsTypeName)) {
            $schemaTypeManager->registerSchemaType(new PermissionsType(SettingVoter::ENTITY_PERMISSIONS, $permissionsTypeName));
        }

        return new ObjectType(
            [
                'name' => IdentifierNormalizer::graphQLType(
                        $identifier,
                        'Setting'
                    ).($nestingLevel > 0 ? 'Level'.$nestingLevel : ''),
                'fields' => array_merge(
                    [
                        'type' => Type::string(),
                        '_permissions' => $schemaTypeManager->getSchemaType($permissionsTypeName, $domain),
                    ],
                    empty($settingType->getLocales()) ? [] : [
                        'locale' => Type::string(),
                        'translations' => [
                            'type' => Type::listOf(
                                $schemaTypeManager->getSchemaType(
                                    IdentifierNormalizer::graphQLType($identifier,'Setting').'Level'.($nestingLevel + 1),
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
                    $fields
                ),
                'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use (
                    $settingType,
                    $fieldTypes
                ) {
                    if (!$value instanceof Setting) {
                        throw new \InvalidArgumentException('Value must be instance of '.Setting::class.'.');
                    }

                    switch ($info->fieldName) {
                        case 'type':
                            return IdentifierNormalizer::graphQLIdentifier($value->getSettingType());
                        case '_permissions':
                            $permissions = [];
                            foreach(SettingVoter::ENTITY_PERMISSIONS as $permission) {
                                $permissions[$permission] = $this->authorizationChecker->isGranted($permission, $value);
                            }
                            return $permissions;

                        case 'locale':
                            return $value->getLocale();
                        case 'translations':

                            $translations = [];
                            $includeLocales = $args['locales'] ?? $value->getSettingType()->getLocales();
                            $includeLocales = is_string($includeLocales) ? [$includeLocales] : $includeLocales;

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

                            return $translations;

                        default:

                            if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                return null;
                            }

                            $fieldData = array_key_exists($info->fieldName, $value->getData()) ? $value->getData(
                            )[$info->fieldName] : null;

                            return $fieldTypes[$info->fieldName]->resolveGraphQLData(
                                $settingType->getFields()->get($info->fieldName),
                                $fieldData,
                                $value
                            );
                    }
                },
                'interfaces' => [$schemaTypeManager->getSchemaType('SettingInterface')],
            ]
        );
    }
}
