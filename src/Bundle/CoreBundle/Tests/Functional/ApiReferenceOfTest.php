<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 13.11.18
 * Time: 17:39
 */

namespace UniteCMS\CoreBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Tests\APITestCase;

class ApiReferenceOfTest extends APITestCase
{
    protected $domainConfig = [
    'domain1' => '{
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
                          "domain": "domain2",
                          "content_type": "ct2"
                        }
                    }
                ]
            }
        ]
    }',
    'domain2' => '{
        "content_types": [
            {
                "title": "CT2",
                "identifier": "ct2",
                "fields": [
                    {
                        "title": "Reference Of",
                        "identifier": "reference_of",
                        "type": "reference_of",
                        "settings": {
                            "domain": "domain1",
                            "content_type": "ct1",
                            "reference_field": "reference"
                        }
                    },
                    {
                        "title": "User Reference",
                        "identifier": "user_reference",
                        "type": "reference",
                        "settings": {
                          "domain": "domain2",
                          "domain_member_type": "editor"
                        }
                    }
                ]
            }
        ],
        "domain_member_types": [
            {
                "title": "Editor",
                "identifier": "editor",
                "fields": [
                    {
                        "title": "User Reference Of",
                        "identifier": "user_reference_of",
                        "type": "reference_of",
                        "settings": {
                            "domain": "domain2",
                            "content_type": "ct2",
                            "reference_field": "user_reference"
                        }
                    }
                ],
                "permissions": {
                    "view member": "true",
                    "list member": "true",
                    "create member": "true",
                    "update member": "true",
                    "delete member": "true"
                }
            }
        ]
    }'
    ];

    /**
     * @var ApiKey $crossDomainAccessor
     */
    protected $crossDomainAccessor;

    public function setUp()
    {
        parent::setUp();
        $this->crossDomainAccessor = new ApiKey();
        $this->crossDomainAccessor->setOrganization($this->organization)->setName('Cross Domain Accessor');

        $this->domains['domain1']->getMembers()->clear();
        $this->domains['domain2']->getMembers()->clear();

        $domainMember1 = new DomainMember();
        $domainMember1->setDomainMemberType($this->domains['domain1']->getDomainMemberTypes()->first())->setId('dm-1-1');
        $this->crossDomainAccessor->addDomain($domainMember1);
        $this->domains['domain1']->addMember($domainMember1);

        $domainMember2 = new DomainMember();
        $domainMember2->setDomainMemberType($this->domains['domain2']->getDomainMemberTypes()->first())->setId('dm-2-1');
        $this->crossDomainAccessor->addDomain($domainMember2);
        $this->domains['domain2']->addMember($domainMember2);
    }

    public function testAccessingReferenceOfForEmptyContent() {

        $refOf = new Content();
        $refOf->setContentType($this->domains['domain2']->getContentTypes()->get('ct2'));
        $this->repositoryFactory->add($refOf);

        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    id,
                    reference_of {
                        total,
                        page, 
                        result {
                            id,
                            created,
                            updated,
                            deleted,
                            reference {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])), true);

        $this->assertEquals(['data' => ['getCt2' => [
            'id' => $refOf->getId(),
            'reference_of' => [
                'total' => 0,
                'page' => 1,
                'result' => [],
            ],
        ]]], $result);
    }

    public function testAccessingReferenceOfForContentWithArguments() {

        $ref1 = new Content();
        $ref1->setContentType($this->domains['domain1']->getContentTypes()->get('ct1'));

        $ref2 = new Content();
        $ref2->setContentType($this->domains['domain1']->getContentTypes()->get('ct1'));

        $ref_other = new Content();
        $ref_other->setContentType($this->domains['domain1']->getContentTypes()->get('ct1'));

        $refOf = new Content();
        $refOf->setContentType($this->domains['domain2']->getContentTypes()->get('ct2'));

        $refOf_other = new Content();
        $refOf_other->setContentType($this->domains['domain2']->getContentTypes()->get('ct2'));

        $this->repositoryFactory->add($ref1);
        $this->repositoryFactory->add($ref2);
        $this->repositoryFactory->add($ref_other);
        $this->repositoryFactory->add($refOf);
        $this->repositoryFactory->add($refOf_other);

        $ref1->setData(['reference' => [
            'domain' => $this->domains['domain2']->getIdentifier(),
            'content_type' => 'ct2',
            'content' => $refOf->getId()
        ]]);

        $ref2->setData(['reference' => [
            'domain' => $this->domains['domain2']->getIdentifier(),
            'content_type' => 'ct2',
            'content' => $refOf->getId()
        ]]);

        $ref_other->setData(['reference' => [
            'domain' => $this->domains['domain2']->getIdentifier(),
            'content_type' => 'ct2',
            'content' => $refOf_other->getId()
        ]]);

        $this->repositoryFactory
            ->getRepository($this->createMock(EntityManagerInterface::class), 'UniteCMSCoreBundle:Content')
            ->queryManipulator = function($objects, $queryParts, $parameters = null, $firstResult = null, $maxResult = null) {

                /**
                 * @var Andx $where
                 */
                $where = $queryParts['where'];

                /**
                 * @var Comparison $comp
                 */
                $comp = $where->getParts()[1];

                // This is just a little mock to see, if setting filters is working
                if($comp instanceof Andx && $comp->getParts()[1]->getLeftExpr() === 'c.created') {
                    return [];
                }

                $objects = array_filter($objects, function($object) use ($comp, $parameters) {
                    if(!$comp->getLeftExpr() === "JSON_EXTRACT(c.data, '$.reference.content')") {
                        return false;
                    }
                    return $object->getData()['reference']['content'] === $parameters[substr($comp->getRightExpr(), 1)];
                });

            // This is just a little mock to see, if setting orderby is working
                if(!empty($queryParts['orderBy'])) {
                    usort($objects, function($a, $b){
                        return $a->getId() < $b->getId();
                    });
                }

                return $objects;
            };

        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    id,
                    reference_of {
                        total,
                        page, 
                        result {
                            id,
                            reference {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])), true);
        $this->assertEquals(['data' => ['getCt2' => [
            'id' => $refOf->getId(),
            'reference_of' => [
                'total' => 2,
                'page' => 1,
                'result' => [
                    [
                        'id' => $ref1->getId(),
                        'reference' => [
                            'id' => $refOf->getId(),
                        ]
                    ],
                    [
                        'id' => $ref2->getId(),
                        'reference' => [
                            'id' => $refOf->getId(),
                        ]
                    ]
                ],
            ],
        ]]], $result);

        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    id,
                    reference_of(sort: {field: "id", order: "DESC"}) {
                        total,
                        page, 
                        result {
                            id,
                            reference {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])), true);
        $this->assertEquals(['data' => ['getCt2' => [
            'id' => $refOf->getId(),
            'reference_of' => [
                'total' => 2,
                'page' => 1,
                'result' => [
                    [
                        'id' => $ref2->getId(),
                        'reference' => [
                            'id' => $refOf->getId(),
                        ]
                    ],
                    [
                        'id' => $ref1->getId(),
                        'reference' => [
                            'id' => $refOf->getId(),
                        ]
                    ]
                ],
            ],
        ]]], $result);

        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    id,
                    reference_of(limit: 1) {
                        total,
                        page, 
                        result {
                            id,
                            reference {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])), true);
        $this->assertEquals(['data' => ['getCt2' => [
            'id' => $refOf->getId(),
            'reference_of' => [
                'total' => 2,
                'page' => 1,
                'result' => [
                    [
                        'id' => $ref1->getId(),
                        'reference' => [
                            'id' => $refOf->getId(),
                        ]
                    ]
                ],
            ],
        ]]], $result);

        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    id,
                    reference_of(limit: 1, page: 2) {
                        total,
                        page, 
                        result {
                            id,
                            reference {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])), true);
        $this->assertEquals(['data' => ['getCt2' => [
            'id' => $refOf->getId(),
            'reference_of' => [
                'total' => 2,
                'page' => 2,
                'result' => [
                    [
                        'id' => $ref2->getId(),
                        'reference' => [
                            'id' => $refOf->getId(),
                        ]
                    ]
                ],
            ],
        ]]], $result);

        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    id,
                    reference_of(filter: {field: "created" operator: "<", value: "1"}) {
                        total,
                        page, 
                        result {
                            id,
                            reference {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])), true);
        $this->assertEquals(['data' => ['getCt2' => [
            'id' => $refOf->getId(),
            'reference_of' => [
                'total' => 0,
                'page' => 1,
                'result' => [],
            ],
        ]]], $result);
    }

    public function testReferenceOfUserField() {

        $refOf = new Content();
        $refOf->setContentType($this->domains['domain2']->getContentTypes()->get('ct2'));

        $refOf_other = new Content();
        $refOf_other->setContentType($this->domains['domain2']->getContentTypes()->get('ct2'));

        $refOf->setData(['user_reference' => [
            'domain' => 'domain2',
            'domain_member_type' => 'editor',
            'content' => 'dm-2-1',
        ]]);

        $refOf_other->setData(['user_reference' => [
            'domain' => 'domain2',
            'domain_member_type' => 'editor',
            'content' => 'dm-2-1',
        ]]);

        $this->repositoryFactory->add($this->crossDomainAccessor->getDomainMembers($this->domains['domain2'])[0]);
        $this->repositoryFactory->add($refOf);
        $this->repositoryFactory->add($refOf_other);

        $this->repositoryFactory
            ->getRepository($this->createMock(EntityManagerInterface::class), 'UniteCMSCoreBundle:Content')
            ->queryManipulator = function($objects, $queryParts, $parameters = null, $firstResult = null, $maxResult = null) {

            /**
             * @var Andx $where
             */
            $where = $queryParts['where'];

            /**
             * @var Comparison $comp
             */
            $comp = $where->getParts()[1];

            $objects = array_filter($objects, function($object) use ($comp, $parameters) {
                if(!$comp->getLeftExpr() === "JSON_EXTRACT(c.data, '$.reference.content')") {
                    return false;
                }
                return $object->getData()['user_reference']['content'] === $parameters[substr($comp->getRightExpr(), 1)];
            });

            // This is just a little mock to see, if setting orderby is working
            if(!empty($queryParts['orderBy'])) {
                usort($objects, function($a, $b){
                    return $a->getId() < $b->getId();
                });
            }

            return $objects;
        };

        // Test accessing user reference -> reference_of
        $result = json_decode(json_encode($this->api('query($id: ID!) {
                getCt2(id: $id) {
                    user_reference {
                        id,
                        user_reference_of {
                            total,
                            result {
                                id
                            }
                        }
                    }
                  }
            }', $this->domains['domain2'], ['id' => $refOf->getId()])));
        $this->assertEquals(2, $result->data->getCt2->user_reference->user_reference_of->total);
        $this->assertEquals([
            (object)['id' => '1-1'],
            (object)['id' => '2-1'],
        ], $result->data->getCt2->user_reference->user_reference_of->result);
    }
}
