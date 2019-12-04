<?php

namespace UniteCMS\MediaBundle\Tests\Field\Type;

use UniteCMS\MediaBundle\Tests\MediaAwareTestCase;
use UniteCMS\MediaBundle\Flysystem\FlySystemManager;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class MediaFileTypeTest extends MediaAwareTestCase
{
    /*public function testS3MediaCreate() {

        $filepath = $this->getTxtFile();

        $s3Config = [
            'key' => 'xxxx',
            'secret' => 'xxxx',
            'region' => '',
            'version' => 'latest',
            'endpoint' => 'https://example.com/',
            'bucket' => 'stefan',
            'path' => '/test',
            'tmppath' => '/tmp'
        ];

        $s3ConfigGraphQL = $this->prepareForGraphQL($s3Config);

        $this->buildSchema('
            type Media implements UniteContent {
                id: ID
                _meta: UniteContentMeta
                alt: String @textField
                title: String @textField
                file: UniteMediaFile @mediaFileField ( s3: '.$s3ConfigGraphQL.')
            }
        ');

        $presigned = 'mutation { uniteMediaPreSignUrl(type:"Media", field:"file") }';

        $this->assertGraphQL([
            'uniteMediaPreSignUrl' => '{uniteMediaPreSignUrl}'
        ], $presigned);


        $result = static::$container->get(SchemaManager::class)->execute($presigned, []);
        $result = $result->toArray(true);
        $presigned_url = $result['data']['uniteMediaPreSignUrl'];

        #$FlySystemManager = new FlySystemManager();
        #$fs = $FlySystemManager->initialize('s3', $s3Config);
        #$filesystem = $fs->getFileSystem();
        #$stream = fopen($filepath, 'r+');
        #$filesystem->writeStream('uploads/test.txt', $stream);
        #fclose($stream);

    }*/
}