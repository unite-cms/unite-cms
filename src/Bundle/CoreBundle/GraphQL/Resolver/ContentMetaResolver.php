<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;

class ContentMetaResolver implements FieldResolverInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteContentMeta';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if(!$value instanceof ContentInterface) {
            throw new InvalidArgumentException(sprintf('ContentMetaResolver expects an instance of %s as value.', ContentInterface::class));
        }

        switch ($info->fieldName) {
            case 'deleted': return $value->getDeleted();
            case 'locale': return null; // TODO: Implement
            case 'version':
                $domain = $this->domainManager->current();
                $versions = $domain->getContentManager()->revisions($domain, $value, 1);
                return count($versions) > 0 ? $versions[0]->getVersion() : 0;
            default: return null;
        }
    }
}
