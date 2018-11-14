<?php

namespace UniteCMS\CoreBundle\SchemaType\Factories;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\SchemaType\Types\ContentResultType;

class ContentResultTypeFactory implements SchemaTypeFactoryInterface
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

        if($nameParts[1] !== 'Content' && $nameParts[2] !== 'Result') {
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
            throw new \InvalidArgumentException('UniteCMS\CoreBundle\SchemaType\Factories\ContentResultTypeFactory::createSchemaType needs an domain as second argument');
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

        $type = new ContentResultType(
            $schemaTypeManager,
            $this->authorizationChecker,
            null,
            $domain,
            IdentifierNormalizer::graphQLType($contentType, 'Content' . ($nestingLevel > 0 ? 'Level' . $nestingLevel : '')),
            $nestingLevel
        );
        $type->name = IdentifierNormalizer::graphQLType($contentType, 'ContentResult' . ($nestingLevel > 0 ? 'Level' . $nestingLevel : ''));
        return $type;
    }
}
