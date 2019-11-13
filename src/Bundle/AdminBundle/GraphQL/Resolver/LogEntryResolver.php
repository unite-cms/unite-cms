<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\Log\LogInterface;

class LogEntryResolver implements FieldResolverInterface
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
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteLogEntry';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        if(!$value instanceof LogInterface) {
            throw new InvalidArgumentException(sprintf('Expect value of type %s', LogInterface::class));
        }

        switch ($info->fieldName) {
            case 'level': return $value->getLevel();
            case 'message': return $value->getMessage();
            case 'created': return $value->getCreated();
            case 'username': return $value->getUsername();
            default: return null;
        }
    }
}
