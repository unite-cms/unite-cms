<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use UniteCMS\CoreBundle\Security\DomainUserProvider;

class UniteLoginResolver implements FieldResolverInterface
{
    /**
     * @var DomainUserProvider $userProvider
     */
    protected $userProvider;

    /**
     * @var UserPasswordEncoderInterface $userPasswordEncoder
     */
    protected $userPasswordEncoder;

    /**
     * @var JWTTokenManagerInterface $tokenManager
     */
    protected $tokenManager;

    public function __construct(DomainUserProvider $userProvider, UserPasswordEncoderInterface $userPasswordEncoder, JWTTokenManagerInterface $tokenManager)
    {
        $this->userProvider = $userProvider;
        $this->userPasswordEncoder = $userPasswordEncoder;
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

        $user = $this->userProvider->loadUserByUsernameAndPayload(
            $args['username'],
            ['roles' => [sprintf('ROLE_%s', $args['type'])]]
        );

        if(!$this->userPasswordEncoder->isPasswordValid($user, $args['password'])) {
            throw new BadCredentialsException();
        }

        return $this->tokenManager->create($user);
    }
}
