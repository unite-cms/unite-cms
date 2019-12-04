<?php

namespace UniteCMS\MediaBundle\Tests\Field\Type;

use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class MediaFileTypeTest extends SchemaAwareTestCase
{
    public function testMediaCreate() {

        $this->buildSchema('
            type Media implements UniteContent {
                id: ID
                _meta: UniteContentMeta
                alt: String @textField
                title: String @textField
                file: UniteMediaFile @mediaFileField ( s3:
                    {
                        key: "'.getenv('s3_key').'",
                        secret: "'.getenv('s3_secret').'",
                        region: "'.getenv('s3_region').'",
                        version: "'.getenv('s3_version').'",
                        endpoint: "'.getenv('s3_endpoint').'"
                        bucket: "'.getenv('s3_bucket').'",
                        path: "'.getenv('s3_path').'"
                    }
                )
            }
        ');

        $presigned = 'mutation { uniteMediaPreSignUrl(type:"Media", field:"file") }';

        $testfile = dirname(__FILE__).'/sample.pdf';

        $this->assertGraphQL([
            'uniteMediaPreSignUrl' => '{uniteMediaPreSignUrl}'
        ], $presigned);


    }
}