<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\UniteCMSCoreBundle;

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

    public function __construct(Security $security, JWTTokenManagerInterface $tokenManager)
    {
        $this->security = $security;
        $this->tokenManager = $tokenManager;
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
                return $this->tokenManager->create($this->security->getUser());
            case '_version':
                return UniteCMSCoreBundle::UNITE_VERSION;
            default: return null;
        }
    }
}
