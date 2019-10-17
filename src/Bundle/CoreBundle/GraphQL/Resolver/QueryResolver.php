<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class QueryResolver implements FieldResolverInterface
{
    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, DomainManager $domainManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->domainManager = $domainManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Query';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        $fieldNameParts = preg_split('/(?=[A-Z])/',$info->fieldName);
        if(count($fieldNameParts) < 2) {
            return null;
        }

        $field = array_shift($fieldNameParts);
        $type = substr($info->fieldName, strlen($field));

        $domain = $this->domainManager->current();
        $contentTypeManager = $domain->getContentTypeManager();
        $contentManager = null;

        if(!empty($contentTypeManager->getContentType($type))) {
            $contentManager = $domain->getContentManager();
        }

        else if(!empty($contentTypeManager->getUserType($type))) {
            $contentManager = $domain->getUserManager();
        }

        else {
            return null;
        }

        switch ($field) {
            case 'get':
                return $this->ifAccess($contentManager->get($domain, $type, $args['id']));
            case 'find':
                return $contentManager->find(
                    $domain,
                    $type,
                    ContentFilterInput::fromInput($args['filter'] ?? []),
                    $args['orderBy'],
                    $args['limit'],
                    $args['offset'],
                    $args['includeDeleted'],
                    [$this, 'ifAccess']
                );
            default:
                return false;
        }
    }

    public function ifAccess(ContentInterface $content = null) : ?ContentInterface {

        if(empty($content)) {
            return null;
        }

        if(!$this->authorizationChecker->isGranted(ContentVoter::READ, $content)) {
            return null;
        }

        return $content;
    }
}
