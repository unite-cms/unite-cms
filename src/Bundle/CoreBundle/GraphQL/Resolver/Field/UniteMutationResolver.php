<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\EventSubscriber\CreateJWTTokenSubscriber;
use UniteCMS\CoreBundle\UniteCMSAdminBundle;

class UniteMutationResolver implements FieldResolverInterface
{
    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var JWTTokenManagerInterface $tokenManager
     */
    protected $tokenManager;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    protected $subscriber;

    public function __construct(Security $security, JWTTokenManagerInterface $tokenManager, DomainManager $domainManager, CreateJWTTokenSubscriber $subscriber)
    {
        $this->security = $security;
        $this->tokenManager = $tokenManager;
        $this->domainManager = $domainManager;
        $this->subscriber = $subscriber;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteMutation';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        switch ($info->fieldName) {

            case 'generateJWT':
                $this->subscriber->setNextTTL($this->domainManager->current()->getJwtTTLShortLiving());
                return $this->tokenManager->create($this->security->getUser());

            case 'generateLongLivingJWT':

                $this->subscriber->setNextTTL($this->domainManager->current()->getJwtTTLLongLiving());
                return $this->tokenManager->create($this->security->getUser());

            case '_version':
                return UniteCMSAdminBundle::UNITE_VERSION;

            default: return null;
        }
    }
}
