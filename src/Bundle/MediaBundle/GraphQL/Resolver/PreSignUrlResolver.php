<?php

namespace UniteCMS\MediaBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\MediaBundle\Flysystem\FlySystemManager;
use UniteCMS\MediaBundle\Flysystem\UploadToken;

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

    /**
     * @var JWTEncoderInterface $JWTEncoder
     */
    protected $JWTEncoder;

    public function __construct(FlySystemManager $flySystemManager, DomainManager $domainManager, JWTEncoderInterface $JWTEncoder) {
        $this->flySystemManager = $flySystemManager;
        $this->domainManager = $domainManager;
        $this->JWTEncoder = $JWTEncoder;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Mutation';
    }

    /**
     * {@inheritDoc}
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
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

                $flySystem = $this->flySystemManager->createFilesystem($driver, $field->getSettings()->get($driver));

                /**
                 * @var UploadToken $uploadToken
                 */
                $uploadToken = $flySystem->createUploadToken($args['filename'], $field->getSettings()->get($driver));
                $uploadToken->setField($field->getId())->setType($field->getType());
                return $this->JWTEncoder->encode($uploadToken->toArray());
            }
        }

        return null;
    }
}
