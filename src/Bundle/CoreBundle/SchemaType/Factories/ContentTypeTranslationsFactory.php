<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentTypeTranslationsFactory implements SchemaTypeFactoryInterface
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
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
        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);

        // Support for content type.
        if(count($nameParts) == 3) {
            if($nameParts[1] == 'Content' && $nameParts[2] == 'Translations') {
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
            throw new \InvalidArgumentException('UniteCMS\CoreBundle\SchemaType\Factories\ContentTypeTranslationsFactory::createSchemaType needs an domain as second argument');
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
        if(!$this->entityManager->contains($contentType)) {
            $contentType = $this->entityManager->getRepository('UniteCMSCoreBundle:ContentType')->find(
                $contentType->getId()
            );
        }

        /**
         * @var Type[] $fields
         */
        $fields = [];

        foreach ($contentType->getLocales() as $locale) {

            $fields[$locale] = $schemaTypeManager->getSchemaType(
                IdentifierNormalizer::graphQLType($identifier, 'Content') .'Level' .  ($nestingLevel + 1),
                $domain,
                ($nestingLevel + 1)
            );
        }

        return new ObjectType(
            [
                'name' => IdentifierNormalizer::graphQLType($identifier, 'ContentTranslations') . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''),
                'fields' => $fields,
                'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use ($contentType) {

                    // TODO
                    var_dump($value);
                },
            ]
        );
    }
}
