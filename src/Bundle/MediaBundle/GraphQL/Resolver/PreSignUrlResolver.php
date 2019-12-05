<?php

namespace UniteCMS\MediaBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\MediaBundle\Flysystem\FlySystemManager;

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
        if ($info->fieldName !== 'uniteMediaPreSignedUrl') {
            return null;
        }

        $domain = $this->domainManager->current();
        $field = $domain->getContentTypeManager()->getContentType($args['type'])->getField($args['field']);
        if (!$field) {
            return null;
        }

        foreach(['s3', 'google', 'local'] as $driver) {
            if(!empty($field->getSettings()->get($driver))) {
                return $this->flySystemManager->createFilesystem($driver, $field->getSettings()->get($driver))->getPreSignedUrl($args['filename']);
            }
        }

        return null;
    }
}
