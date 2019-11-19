<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentRevisionInterface;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\Content\RevisionContent;

class ContentRevisionResolver implements FieldResolverInterface
{
    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteContentRevision';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if(!$value instanceof ContentRevisionInterface) {
            throw new InvalidArgumentException(sprintf('ContentRevisionResolver expects an instance of %s as value.', ContentRevisionInterface::class));
        }

        switch ($info->fieldName) {

            case 'operation':
                return $value->getOperation();

            case 'version':
                return $value->getVersion();

            case 'operationTime':
                return $value->getOperationTime();

            case 'operatorName':
                return $value->getOperatorName();

            case 'operatorType':
                return $value->getOperatorType();

            case 'operatorId':
                return $value->getOperatorId();

            case 'content':
                return new RevisionContent($value->getEntityType(), $value->getData());

            default: return null;
        }
    }
}
