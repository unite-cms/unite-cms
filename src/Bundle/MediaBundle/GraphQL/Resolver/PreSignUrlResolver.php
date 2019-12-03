<?php


namespace UniteCMS\MediaBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\MediaBundle\Plugin\S3PresignedUrl;
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

        print_r($args);


        $client = new S3Client([
            'credentials' => [
                'key'    => '622E11788582AC29',
                'secret' => '584e6bdeb7c34a52180e68f5a81d3142f3526f30'
            ],
            'region' => '',
            'version' => 'latest',
            'endpoint' => 'https://minio.apps.unitecms.io/',
        ]);

        $adapter = new AwsS3Adapter($client, 'stefan');
        $filesystem = new Filesystem($adapter);

        $filesystem->addPlugin(new S3PresignedUrl());

        $success = $filesystem->getPresignedUrl('/tmp');
        dump($success);
        exit;

        return "TODO";
    }
}
