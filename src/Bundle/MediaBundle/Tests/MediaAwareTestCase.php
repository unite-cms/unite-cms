<?php

namespace UniteCMS\MediaBundle\Tests;

use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class MediaAwareTestCase extends SchemaAwareTestCase
{
    protected function getTxtFile() {
        $file = '/tmp/test.txt';
        $contents = 'test123';
        file_put_contents($file, $contents);
        return $file;
    }

    protected function prepareForGraphQL($s3Config) {
    	$json = json_encode($s3Config, JSON_UNESCAPED_SLASHES);
    	$json = preg_replace('/"([^"]+)"\s*:\s*/', '$1:', $json);
    	return $json;
    }
    
}