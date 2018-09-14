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
use UniteCMS\CoreBundle\Exception\ContentTypeAccessDeniedException;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;

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

    public function __construct(EntityManager $entityManager, AuthorizationChecker $authorizationChecker)
    {
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

        // Support for setting type.
        if(count($nameParts) == 3) {
            if($nameParts[1] == 'Setting' && $nameParts[2] == 'Translations') {
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
            throw new \InvalidArgumentException('UniteCMS\CoreBundle\SchemaType\Factories\SettingTypeTranslationsFactory::createSchemaType needs an domain as second argument');
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
        if(!$this->entityManager->contains($settingType)) {
            $settingType = $this->entityManager->getRepository('UniteCMSCoreBundle:SettingType')->find(
                $settingType->getId()
            );
        }

        /**
         * @var Type[] $fields
         */
        $fields = [];

        foreach ($settingType->getLocales() as $locale) {

            $fields[$locale] = $schemaTypeManager->getSchemaType(
                IdentifierNormalizer::graphQLType($identifier, 'Setting') .'Level' .  ($nestingLevel + 1),
                $domain,
                ($nestingLevel + 1)
            );
        }

        return new ObjectType(
            [
                'name' => IdentifierNormalizer::graphQLType($identifier, 'SettingTranslations') . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''),
                'fields' => $fields,
                'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use ($settingType) {

                    if(!empty($value[$info->fieldName]) && $value[$info->fieldName] instanceof Setting) {

                        if (!$this->authorizationChecker->isGranted(SettingVoter::VIEW, $value[$info->fieldName])) {
                            throw new ContentTypeAccessDeniedException("You are not allowed to access \"{$info->fieldName}\" translation of \"{$value[$info->fieldName]->getSettingType()->getTitle()}\" setting.");
                        }

                        return $value[$info->fieldName];
                    }

                    return null;
                },
            ]
        );
    }
}
