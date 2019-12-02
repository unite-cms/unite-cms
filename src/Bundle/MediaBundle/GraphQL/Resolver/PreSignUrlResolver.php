<?php


namespace UniteCMS\MediaBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use Etime\Flysystem\Plugin\AWS_S3 as AWS_S3_Plugin;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Aws\S3\S3Client;

class PreSignUrlResolver implements FieldResolverInterface
{

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
        if($info->fieldName !== 'uniteMediaPreSignUrl') {
            return null;
        }

        $client = new S3Client([
            'credentials' => [
                'key'    => 'your-key',
                'secret' => 'your-secret'
            ],
            'region' => 'your-region',
            'version' => 'latest|version',
        ]);

        $adapter = new AwsS3Adapter($client, 'your-bucket-name');
        $filesystem = new Filesystem($adapter);

        $filesystem->addPlugin(new AWS_S3_Plugin\PresignedUrl());

        $success = $filesystem->getPresignedUrl('/tmp');
        dump($success);
        exit;

        return "TODO";
    }
}
