<?php


namespace UniteCMS\MediaBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\MediaBundle\Flysystem\FlySystemManager;

class UniteMediaFileResolver implements FieldResolverInterface
{
    /**
     * @var FlySystemManager $flySystemManager
     */
    protected $flySystemManager;

    public function __construct(FlySystemManager $flySystemManager)
    {
        $this->flySystemManager = $flySystemManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool
    {
        return $typeName === 'UniteMediaFile';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        if(!is_array($value) || empty($value) || empty($value['driver'])) {
            return null;
        }

        switch ($info->fieldName) {

            case 'id':
            case 'filename':
            case 'driver':
            case 'filesize':
            case 'mimetype':
                return $value[$info->fieldName];

            case 'url':

                $flySystem = $this->flySystemManager->createFilesystem($value['driver'], $value['config']);
                return $flySystem->getDownloadUrl($value['id'], $value['filename'], $args['pre_sign'] ?? false, $value['config'] ?? []);

            case 'preview':

                if(substr($value['mimetype'], 0, strlen('image/')) !== 'image/') {
                    return null;
                }

                $flySystem = $this->flySystemManager->createFilesystem($value['driver'], $value['config']);
                return $flySystem->getDownloadUrl($value['id'], $value['filename'], $args['pre_sign'] ?? false, $value['config'] ?? []);

            default: return null;
        }
    }
}
