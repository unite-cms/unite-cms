<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use GraphQL\Error\UserError;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\DoctrineORMBundle\Entity\Content;

class MutationResolver implements FieldResolverInterface
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
        return $typeName === 'Mutation';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        $actualType = $info->returnType;

        if($actualType instanceof WrappingType) {
            $actualType = $actualType->getWrappedType(true);
        }

        /**
         * @var InterfaceType $contentInterface
         */
        $contentInterface = $info->schema->getType('UniteContent');

        if(!$actualType instanceof ObjectType || !$actualType->implementsInterface($contentInterface)) {
            return null;
        }

        list($field, $type) = preg_split('/(?=[A-Z])/',$info->fieldName);

        $domain = $this->domainManager->current();
        $contentManager = $domain->getContentManager();

        switch ($field) {
            case 'create':
                $this->contentOrException($domain, $type, ContentVoter::CREATE);
                return $contentManager->create($domain, $type, $args['data'] ?? [], $args['persist']);

            case 'update':
                $content = $this->contentOrException($domain, $type, ContentVoter::UPDATE);
                return $contentManager->update($domain, $type, $content, $args['data'] ?? [], $args['persist']);

            case 'delete':
                $content = $this->contentOrException($domain, $type, ContentVoter::DELETE);
                return $contentManager->delete($domain, $type, $content, $args['persist']);

            default:
                return null;
        }
    }

    protected function contentOrException(Domain $domain, string $type, string $attribute, $id = null) : ?ContentInterface {

        if($attribute === ContentVoter::CREATE) {
            $subject = $domain->getContentTypeManager()->getContentType($type);

            if(empty($subject)) {
                throw new InvalidArgumentException(sprintf('Content type %s was not found.', $type));
            }

        } else {
            $subject = $domain->getContentManager()->find($domain, $type, $id);

            if(empty($subject)) {
                return null;
            }
        }

        if(!$this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException(sprintf('You are not allowed to %s content of type %s.', $attribute, $type));
        }

        return $subject instanceof ContentInterface ? $subject : null;
    }
}
