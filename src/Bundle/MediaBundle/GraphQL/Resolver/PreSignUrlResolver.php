<?php

namespace UniteCMS\MediaBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\MediaBundle\FlySystem\FlySystemManager;

class PreSignUrlResolver implements FieldResolverInterface
{
    /**
     * @var FlySystemManager $flySystemManager;
     */
    protected $flySystemManager;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(FlySystemManager $flySystemManager, DomainManager $domainManager) {
        $this->flySystemManager = $flySystemManager;
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Mutation';
    }

    /**
     * {@inheritDoc}
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        if ($info->fieldName !== 'uniteMediaPreSignUrl') {
            return null;
        }

        $domain = $this->domainManager->current();
        $s3Config = $domain->getContentTypeManager()->getContentType($args['type'])->getField($args['field'])->getSettings()->get('s3');
        if (!$s3Config or empty($s3Config)) {
            return null;
        }
        $this->flySystemManager->initiate($s3Config);
        return $this->flySystemManager->getPresignedUrl();
    }
}
