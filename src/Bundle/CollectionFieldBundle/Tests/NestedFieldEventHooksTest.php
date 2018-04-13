<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 15.02.18
 * Time: 15:41
 */

namespace UniteCMS\CollectionFieldBundle\Tests\Field;

use Doctrine\ORM\EntityRepository;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class FieldEventHooksTest extends DatabaseAwareTestCase
{
    private $domainConfig = '
    {
        "title": "Domain",
        "identifier": "d",
        "content_types": [
            {
                "title": "CT 1",
                "identifier": "ct1",
                "fields": [
                    {
                        "title": "Nested Level 1",
                        "identifier": "n1",
                        "type": "collection",
                        "settings": {
                            "fields": [
                                {
                                    "title": "Nested Level 2",
                                    "identifier": "n2",
                                    "type": "collection",
                                    "settings": {
                                        "fields": [
                                            {
                                                "title": "Test",
                                                "identifier": "test",
                                                "type": "testeventhook"
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    }
                ]
            }
        ],
        "setting_types": [
            {
                "title": "ST 1",
                "identifier": "st1",
                "fields": [
                    {
                        "title": "Nested Level 1",
                        "identifier": "n1",
                        "type": "collection",
                        "settings": {
                            "fields": [
                                {
                                    "title": "Nested Level 2",
                                    "identifier": "n2",
                                    "type": "collection",
                                    "settings": {
                                        "fields": [
                                            {
                                                "title": "Test",
                                                "identifier": "test",
                                                "type": "testeventhook"
                                            }
                                        ]
                                    }
                                }
                            ]
                        }
                    }
                ]
            }
        ]
    }';

    /**
     * @var Domain $domain
     */
    private $domain;

    public function setUp()
    {
        parent::setUp();

        $org = new Organization();
        $org->setIdentifier('org')->setTitle('Org');
        $this->em->persist($org);
        $this->em->flush($org);

        $this->domain = $this->container->get('unite.cms.domain_definition_parser')->parse($this->domainConfig);
        $this->domain->setOrganization($org);
        $this->em->persist($this->domain);
        $this->em->flush($this->domain);
    }

    public function testNestedEvents() {

        $mock = new class extends FieldType {

            public $softDeleteString = [];
            public $hardDeleteString = [];

            public function createCompareAbleString(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
                return
                  $field->getJsonExtractIdentifier() .
                  $content->getEntity()->getIdentifier() .
                  $repository->getClassName() .
                  $data;
            }

            const TYPE = 'testeventhook';
            public function onCreate(FieldableField $field, Content $content, EntityRepository $repository, &$data) {
                $data[$field->getIdentifier()] = $this->createCompareAbleString($field, $content, $repository, $data[$field->getIdentifier()]);
            }
            public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
                $data[$field->getIdentifier()] = $this->createCompareAbleString($field, $content, $repository, $data[$field->getIdentifier()]);
            }
            public function onSoftDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {
                $this->softDeleteString[$field->getJsonExtractIdentifier()] = $this->createCompareAbleString($field, $content, $repository, 'soft_delete');
            }
            public function onHardDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {
                $this->hardDeleteString[$field->getJsonExtractIdentifier()] = $this->createCompareAbleString($field, $content, $repository, 'hard_delete');
            }
        };

        $this->container->get('unite.cms.field_type_manager')->registerFieldType($mock);

        $content = new Content();
        $content->setContentType($this->domain->getContentTypes()->first());
        $content->setData([
          'n1' => [
            [
              'n2' => [
                ['test' => 'foo'],
                ['test' => 'baa'],
              ]
            ],
            [
              'n2' => [
                ['test' => 'luu'],
                ['test' => 'laa'],
              ]
            ],
          ],
        ]);

        $this->em->persist($content);
        $this->em->flush($content);
        $this->em->refresh($content);

        // Make sure, that nested create event was fired on mock.
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'foo', $content->getData()['n1'][0]['n2'][0]['test']);
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'baa', $content->getData()['n1'][0]['n2'][1]['test']);
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'luu', $content->getData()['n1'][1]['n2'][0]['test']);
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'laa', $content->getData()['n1'][1]['n2'][1]['test']);

        // Update content
        $content->setData([
          'n1' => [
            [
              'n2' => [
                ['test' => 'updated_foo'],
                ['test' => 'updated_baa'],
              ]
            ],
            [
              'n2' => [
                ['test' => 'updated_luu'],
                ['test' => 'updated_laa'],
              ]
            ],
          ],
        ]);

        $this->em->flush($content);

        // Make sure, that nested create event was fired on mock.
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'updated_foo', $content->getData()['n1'][0]['n2'][0]['test']);
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'updated_baa', $content->getData()['n1'][0]['n2'][1]['test']);
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'updated_luu', $content->getData()['n1'][1]['n2'][0]['test']);
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'updated_laa', $content->getData()['n1'][1]['n2'][1]['test']);

        // Soft delete content
        $this->em->remove($content);
        $this->em->flush();

        // softDelete should be invoked.
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'soft_delete', $mock->softDeleteString['$.n1[*].n2[*].test']);

        // Remove it for real.
        $this->em->getFilters()->disable('gedmo_softdeleteable');

        $content = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy([
          'contentType' => $this->domain->getContentTypes()->first(),
        ]);

        $this->em->remove($content);
        $this->em->flush();
        $this->em->getFilters()->enable('gedmo_softdeleteable');

        // hardDelete should be invoked.
        $this->assertEquals('$.n1[*].n2[*].testct1' . Content::class . 'hard_delete', $mock->hardDeleteString['$.n1[*].n2[*].test']);
    }
}
