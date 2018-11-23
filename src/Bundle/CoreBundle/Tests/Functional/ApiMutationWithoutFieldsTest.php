<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 13.11.18
 * Time: 17:39
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use UniteCMS\CoreBundle\Tests\APITestCase;

class ApiMutationWithoutFieldsTest extends APITestCase
{
    protected $domainConfig = [
    'domain' => '{
        "content_types": [
            {
                "title": "CT1",
                "identifier": "ct1",
                "fields": [
                    {
                        "title": "Reference",
                        "identifier": "reference",
                        "type": "reference",
                        "settings": {
                          "domain": "domain",
                          "content_type": "ct2"
                        }
                    }
                ]
            },
            {
                "title": "CT2",
                "identifier": "ct2",
                "fields": [
                    {
                        "title": "Reference Of",
                        "identifier": "reference_of",
                        "type": "reference_of",
                        "settings": {
                            "domain": "domain",
                            "content_type": "ct1",
                            "reference_field": "reference"
                        }
                    }
                ]
            },
            {
                "title": "CT3",
                "identifier": "ct3",
                "fields": []
            }
        ]
    }',];

    public function testMutatingCTWithoutFields() {

        $result = json_decode(json_encode($this->api('mutation {
                createCt3(persist: false) {
                    id
                  }
            }')), true);

        $this->assertTrue(empty($result['errors']));
        $this->assertEquals(['createCt3' => ['id' => null]], $result['data']);
    }

    public function testMutatingCTWithoutInputFields() {

        $result = json_decode(json_encode($this->api('mutation {
                createCt2(persist: false) {
                    id
                  }
            }')), true);

        $this->assertTrue(empty($result['errors']));
        $this->assertEquals(['createCt2' => ['id' => null]], $result['data']);
    }
}
