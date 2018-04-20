<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use App\Bundle\CoreBundle\Exception\AccessDeniedException;
use App\Bundle\CoreBundle\Exception\InvalidFieldConfigurationException;
use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

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

        if(count($nameParts) !== 2) {
            return false;
        }

        if($nameParts[1] !== 'Setting') {
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
    public function createSchemaType(SchemaTypeManager $schemaTypeManager, int $nestingLevel, Domain $domain = null, string $schemaTypeName): Type
    {
        if(!$domain) {
            throw new \InvalidArgumentException('UniteCMS\CoreBundle\SchemaType\Factories\SettingTypeFactory::createSchemaType needs an domain as second argument');
        }

        $nameParts = preg_split('/(?=[A-Z])/', $schemaTypeName, -1, PREG_SPLIT_NO_EMPTY);
        $identifier = strtolower($nameParts[0]);

        /**
         * @var SettingType $settingType
         */
        if (!$settingType = $domain->getSettingTypes()->get($identifier)) {
            throw new \InvalidArgumentException(
                "No settingType with identifier '$identifier' found for in the given domain."
            );
        }

        // Load the full settingType if it is not already loaded.
        if(!$this->entityManager->contains($settingType)) {
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
            try {
                $fieldTypes[$field->getIdentifier()] = $this->fieldTypeManager->getFieldType($field->getType());
                $fields[$field->getIdentifier()] = $fieldTypes[$field->getIdentifier()]->getGraphQLType(
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
                'name' => ucfirst($identifier).'Setting'  . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''),
                'fields' => array_merge(
                    [
                        'type' => Type::string(),
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
                            return $value->getSettingType()->getIdentifier();
                        default:

                            if (!array_key_exists($info->fieldName, $fieldTypes)) {
                                return null;
                            }

                            $fieldData = array_key_exists($info->fieldName, $value->getData()) ? $value->getData(
                            )[$info->fieldName] : null;
                            $data = $fieldTypes[$info->fieldName]->resolveGraphQLData($settingType->getFields()->get($info->fieldName), $fieldData);

                            return $data;
                    }
                },
                'interfaces' => [$schemaTypeManager->getSchemaType('SettingInterface')],
            ]
        );
    }
}
