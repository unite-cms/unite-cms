<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use InvalidArgumentException;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class ContentMetaResolver implements FieldResolverInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var Security $security
     */
    protected $security;

    public function __construct(DomainManager $domainManager, Security $security)
    {
        $this->domainManager = $domainManager;
        $this->security = $security;
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
            case 'id': return $value->getId();
            case 'deleted': return $value->getDeleted();
            case 'locale': return null; // TODO: Implement
            case 'permissions':
                $permissions = [];
                foreach(ContentVoter::ENTITY_PERMISSIONS as  $permission) {
                    $permissions[$permission] = $this->security->isGranted($permission, $value);
                }
                return $permissions;
            case 'version':
                $domain = $this->domainManager->current();
                $versions = $domain->getContentManager()->revisions($domain, $value, 1);
                return count($versions) > 0 ? $versions[0]->getVersion() : 0;
            default: return null;
        }
    }
}
