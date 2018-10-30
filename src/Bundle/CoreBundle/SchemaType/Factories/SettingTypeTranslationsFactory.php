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
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class SettingTypeTranslationsFactory implements SchemaTypeFactoryInterface
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

        // Support for setting type.
        if (count($nameParts) == 3) {
            if ($nameParts[1] == 'Setting' && $nameParts[2] == 'Translations') {
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
                'UniteCMS\CoreBundle\SchemaType\Factories\SettingTypeTranslationsFactory::createSchemaType needs an domain as second argument'
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

//        foreach ($settingType->getLocales() as $locale) {
//
//            $fields[$locale] = $schemaTypeManager->getSchemaType(
//                IdentifierNormalizer::graphQLType($identifier, 'Setting').'Level'.($nestingLevel + 1),
//                $domain,
//                ($nestingLevel + 1)
//            );
//        }


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

        return new ObjectType(
            [
                'name' => IdentifierNormalizer::graphQLType(
                        $identifier,
                        'SettingTranslations'
                    ).($nestingLevel > 0 ? 'Level'.$nestingLevel : ''),
                'fields' => array_merge(
                    [
                        'type' => Type::string(),
                    ],
                    empty($settingType->getLocales()) ? [] : [
                        'locale' => Type::string(),
                    ],
                    $fields
                ),
                'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use ($settingType) {
                    if (!empty($value) && $value instanceof Setting) {
                        switch ($info->fieldName) {
                            case 'type':
                                return IdentifierNormalizer::graphQLIdentifier($value->getSettingType());
                            case 'locale':
                                return $value->getLocale();
                            default:
                                if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                    return null;
                                }

                                $fieldData = array_key_exists($info->fieldName, $value->getData()) ? $value->getData()[$info->fieldName] : null;

                                return $fieldTypes[$info->fieldName]->resolveGraphQLData(
                                    $settingType->getFields()->get($info->fieldName),
                                    $fieldData
                                );
                        }
                    }

                    return null;
                },
                'interfaces' => [$schemaTypeManager->getSchemaType('SettingInterface')],
            ]
        );
    }
}
