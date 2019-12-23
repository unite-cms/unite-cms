<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Type;

use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;

class UniteContentResolver implements TypeResolverInterface
{
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, TypeDefinitionNode $typeDefinitionNode): bool {
        if($typeDefinitionNode instanceof InterfaceTypeDefinitionNode) {
            return in_array($typeDefinitionNode->name->value, ['UniteFieldable', 'UniteContent', 'UniteUser', 'UniteEmbeddedContent', 'UniteSingleContent', 'UniteTranslatableContent']);
        }

        if($typeDefinitionNode instanceof UnionTypeDefinitionNode) {
            return $this->domainManager->current()->getContentTypeManager()->getUnionContentType($typeDefinitionNode->name->value) !== null;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $context, ResolveInfo $info)
    {
        if(!$value instanceof ContentInterface) {
            throw new InvalidArgumentException('TypeResolver for UniteContent expects an ContentInterface value.');
        }

        return $info->schema->getType($value->getType());
    }
}
