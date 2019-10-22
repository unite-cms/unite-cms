<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Security\Core\Security;
use UniteCMS\CoreBundle\UniteCMSCoreBundle;

class UniteQueryResolver implements FieldResolverInterface
{
    /**
     * @var Security $security
     */
    protected $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteQuery';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        switch ($info->fieldName) {
            case 'me':
                return $this->security->getUser();
            case '_version':
                return UniteCMSCoreBundle::UNITE_VERSION;
            default: return null;
        }
    }
}
