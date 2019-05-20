<?php

namespace UniteCMS\CoreBundle\Tests\Security;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class FieldableFieldVoterGraphQLTest extends DatabaseAwareTestCase
{

    /**
     * @var Domain
     */
    protected $domain;

    /**
     * @var Content
     */
    protected $content;

    /**
     * @var ContentType
     */
    protected $contentType;

    /**
     * @var UsernamePasswordToken[] $users
     */
    protected $users = [];

    protected $schema;

    public function setUp()
    {
        parent::setUp();

        $org = new Organization();
        $org->setIdentifier('org')->setTitle('Org');

        $this->domain = new Domain();
        $this->domain->setOrganization($org)->setTitle('Domain')->setIdentifier('domain');

        $this->contentType = new ContentType();
        $this->contentType->setTitle('CT')->setIdentifier('ct');
        $this->contentType->setDomain($this->domain);
        $this->contentType->setPermissions([ContentVoter::CREATE => 'true', ContentVoter::UPDATE => 'true']);

        $f1 = new ContentTypeField();
        $f1->setTitle('F1')->setIdentifier('f1')->setType('text');
        $this->contentType->addField($f1);

        $f2 = new ContentTypeField();
        $f2->setTitle('F2')->setIdentifier('f2')->setPermissions([
            FieldableFieldVoter::LIST => 'true',
            FieldableFieldVoter::VIEW => 'true',
            FieldableFieldVoter::UPDATE => 'false',
        ])->setType('text');
        $this->contentType->addField($f2);

        $f3 = new ContentTypeField();
        $f3->setTitle('F3')->setIdentifier('f3')->setPermissions([
            FieldableFieldVoter::LIST => 'true',
            FieldableFieldVoter::VIEW => 'false',
            FieldableFieldVoter::UPDATE => 'true',
        ])->setType('text');
        $this->contentType->addField($f3);

        $f4 = new ContentTypeField();
        $f4->setTitle('F4')->setIdentifier('f4')->setPermissions([
            FieldableFieldVoter::LIST => 'false',
            FieldableFieldVoter::VIEW => 'false',
            FieldableFieldVoter::UPDATE => 'false',
        ])->setType('text');
        $this->contentType->addField($f4);

        $f5 = new ContentTypeField();
        $f5->setTitle('F5')->setIdentifier('f5')->setPermissions([
            FieldableFieldVoter::LIST => 'member.type == "editor"',
            FieldableFieldVoter::VIEW => 'content.data.f1 == "A" || content.data.f1 == "B"',
            FieldableFieldVoter::UPDATE => 'content.data.f1 == "A"',
        ])->setType('text');
        $this->contentType->addField($f5);

        $this->content = new Content();
        $this->content->setContentType($this->contentType);


        $apiKey1 = new ApiKey();
        $apiKey1->setToken('XXX1')->setName('XXX1')->setOrganization($org);

        $adminDomainMember1 = new DomainMember();
        $adminDomainMember1->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('editor'));
        $apiKey1->addDomain($adminDomainMember1);

        $apiKey2 = new ApiKey();
        $apiKey2->setToken('XXX2')->setName('XXX2')->setOrganization($org);

        $adminDomainMember2 = new DomainMember();
        $adminDomainMember2->setDomain($this->domain)->setDomainMemberType($this->domain->getDomainMemberTypes()->get('viewer'));
        $apiKey2->addDomain($adminDomainMember2);

        $this->em->persist($org);
        $this->em->persist($this->domain);
        $this->em->persist($apiKey1);
        $this->em->persist($apiKey2);
        $this->em->flush();

        $this->users['editor'] = new UsernamePasswordToken($apiKey1, 'password', 'api', $apiKey1->getRoles());
        $this->users['viewer'] = new UsernamePasswordToken($apiKey2, 'password', 'api', $apiKey2->getRoles());

        $m = static::$container->get('unite.cms.graphql.schema_type_manager');
        $this->schema = $m->createSchema($this->domain, 'Query', 'Mutation');

        $d = new \ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'),$this->domain);
    }

    public function testGraphQLSchemaGenerationForEditor() {
        $m = static::$container->get('unite.cms.graphql.schema_type_manager');
        static::$container->get('security.token_storage')->setToken($this->users['editor']);
        $this->assertEquals(['id', 'type', '_permissions', 'created', 'updated', 'deleted', 'f1', 'f2', 'f3', 'f5'], array_keys($m->getSchemaType('CtContent', $this->domain)->getFields()));
        $this->assertEquals(['f1', 'f2', 'f3', 'f5'], array_keys($m->getSchemaType('CtContentInput', $this->domain)->getFields()));

        $result = GraphQL::executeQuery(
            $this->schema,
            'mutation { 
                createCt(
                    persist: true,
                    data: {
                        f1: "F1 Created",
                        f2: "F2 Created",
                        f3: "F3 Created",
                        f5: "F5 Created"
                    }
                ) 
                {
                    id, f1, f2, f3, f5
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals('F1 Created', $result->data->createCt->f1);
        $this->assertEquals('', $result->data->createCt->f2);
        $this->assertNull($result->data->createCt->f3);
        $this->assertNull($result->data->createCt->f5);

        $getFullContent = $this->em->getRepository(Content::class)->find($result->data->createCt->id);
        $this->assertEquals([
            'f1' => 'F1 Created',
            'f3' => 'F3 Created',
        ], $getFullContent->getData());

        $getFullContent->setData([
            'f1' => 'A',
            'f2' => 'F2',
            'f3' => 'F3',
            'f4' => 'F4',
            'f5' => 'F5',
        ]);
        $this->em->flush();

        $result = GraphQL::executeQuery(
            $this->schema,
            'mutation { 
                updateCt(
                    id: "'. $getFullContent->getId() .'"
                    persist: true,
                    data: {
                        f2: "F1 Updated",
                        f3: "F3 Updated",
                        f5: "F5 Updated"
                    }
                ) 
                {
                    id, f1, f2, f3, f5
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals('A', $result->data->updateCt->f1);
        $this->assertEquals('', $result->data->updateCt->f2);
        $this->assertEquals('F5 Updated', $result->data->updateCt->f5);
        $this->assertNull($result->data->updateCt->f3);

        $getFullContent = $this->em->getRepository(Content::class)->find($result->data->updateCt->id);
        $this->assertEquals([
            'f1' => 'A',
            'f3' => 'F3 Updated',
            'f5' => 'F5 Updated',
        ], $getFullContent->getData());

    }

    public function testGraphQLSchemaGenerationForViewer() {
        $m = static::$container->get('unite.cms.graphql.schema_type_manager');
        static::$container->get('security.token_storage')->setToken($this->users['viewer']);
        $this->assertEquals(['id', 'type', '_permissions', 'created', 'updated', 'deleted', 'f1', 'f2', 'f3'], array_keys($m->getSchemaType('CtContent', $this->domain)->getFields()));
        $this->assertEquals(['f1', 'f2', 'f3'], array_keys($m->getSchemaType('CtContentInput', $this->domain)->getFields()));

        $result = GraphQL::executeQuery(
            $this->schema,
            'mutation { 
                createCt(
                    persist: true,
                    data: {
                        f1: "F1 Created",
                        f2: "F2 Created",
                        f3: "F3 Created"
                    }
                ) 
                {
                    id, f1, f2, f3
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals('F1 Created', $result->data->createCt->f1);
        $this->assertEquals('', $result->data->createCt->f2);
        $this->assertNull($result->data->createCt->f3);

        $getFullContent = $this->em->getRepository(Content::class)->find($result->data->createCt->id);
        $this->assertEquals([
            'f1' => 'F1 Created',
            'f3' => 'F3 Created',
        ], $getFullContent->getData());

        $getFullContent->setData([
            'f1' => 'A',
            'f2' => 'F2',
            'f3' => 'F3',
            'f4' => 'F4',
            'f5' => 'F5',
        ]);
        $this->em->flush();

        $result = GraphQL::executeQuery(
            $this->schema,
            'mutation { 
                updateCt(
                    id: "'. $getFullContent->getId() .'"
                    persist: true,
                    data: {
                        f2: "F1 Updated",
                        f3: "F3 Updated"
                    }
                ) 
                {
                    id, f1, f2, f3
                }
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals('A', $result->data->updateCt->f1);
        $this->assertEquals('', $result->data->updateCt->f2);
        $this->assertNull($result->data->updateCt->f3);

        $getFullContent = $this->em->getRepository(Content::class)->find($result->data->updateCt->id);
        $this->assertEquals([
            'f1' => 'A',
            'f3' => 'F3 Updated',
            'f5' => 'F5',
        ], $getFullContent->getData());
    }
}
