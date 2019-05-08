<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\SchemaType\Types\FieldableContentResultType;

class FieldableResultTypeFactory implements SchemaTypeFactoryInterface
{
    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    public function __construct(AuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Returns a list of GraphQL schema type names that are supported by this factory.
     */
    public function getSupportedEntities() : array {

        $contentType = new ContentType();
        $domainMemberType = new DomainMemberType();

        $names = [
            IdentifierNormalizer::graphQLType($contentType),
            IdentifierNormalizer::graphQLType($domainMemberType),
        ];

        unset($contentType);
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

        if(count($nameParts) !== 3) {
            return false;
        }

        if(in_array($nameParts[1], $this->getSupportedEntities()) && $nameParts[2] !== 'Result') {
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
            throw new \InvalidArgumentException('UniteCMS\CoreBundle\SchemaType\Factories\FieldableResultTypeFactory::createSchemaType needs an domain as second argument');
        }

        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);
        $identifier = IdentifierNormalizer::fromGraphQLSchema($schemaTypeName);
        $entityType = $nameParts[1];

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
        }

        else if($entityType === 'Member' && $domain->getDomainMemberTypes()->get($identifier)) {
            $fieldable = $domain->getDomainMemberTypes()->get($identifier);
        }

        if (!$fieldable) {
            throw new \InvalidArgumentException(
                "No '$entityType' type with identifier '$identifier' found for in the given domain."
            );
        }

        $type = new FieldableContentResultType(
            $schemaTypeManager,
            $this->authorizationChecker,
            null,
            $domain,
            $fieldable,
            IdentifierNormalizer::graphQLType($fieldable, $entityType . ($nestingLevel > 0 ? 'Level' . $nestingLevel : '')),
            $nestingLevel
        );
        $type->name = IdentifierNormalizer::graphQLType($fieldable, $entityType . 'Result' . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''));
        return $type;
    }
}
