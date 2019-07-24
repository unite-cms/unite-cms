<?php

namespace UniteCMS\CoreBundle\Tests\Security;

use GraphQL\Error\Error;
use GraphQL\GraphQL;
use ReflectionProperty;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
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
     * @var SettingType
     */
    protected $settingType;

    /**
     * @var UsernamePasswordToken[] $users
     */
    protected $users = [];

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

        $f6 = new ContentTypeField();
        $f6->setTitle('F6')->setIdentifier('f6')->setPermissions([
            FieldableFieldVoter::LIST => 'false',
            FieldableFieldVoter::VIEW => 'true',
            FieldableFieldVoter::UPDATE => 'true',
        ])->setType('text');
        $this->contentType->addField($f6);

        $this->content = new Content();
        $this->content->setContentType($this->contentType);

        $this->settingType = new SettingType();
        $this->settingType->setPermissions([SettingVoter::VIEW => 'true', SettingVoter::UPDATE => 'true']);
        $this->settingType->setTitle('ST')->setIdentifier('st');
        $this->settingType->setDomain($this->domain);
        $sf1 = new SettingTypeField();
        $sf1->setTitle('F1')->setIdentifier('f1')->setType('text')->setPermissions([
            FieldableFieldVoter::LIST => 'member.type == "editor"',
            FieldableFieldVoter::VIEW => 'content.data.f1 == "A" || content.data.f1 == "B"',
            FieldableFieldVoter::UPDATE => 'content.data.f1 == "A"',
        ]);
        $this->settingType->addField($sf1);

        $mf1 = new DomainMemberTypeField();
        $mf1->setTitle('F1')->setIdentifier('f1')->setType('text')->setPermissions([
            FieldableFieldVoter::LIST => 'member.type == "editor"',
            FieldableFieldVoter::VIEW => 'content.data.f1 == "A" || content.data.f1 == "B"',
            FieldableFieldVoter::UPDATE => 'content.data.f1 == "A"',
        ]);
        $this->domain->getDomainMemberTypes()->get('editor')->addField($mf1);
        $this->domain->getDomainMemberTypes()->get('editor')->setPermissions([DomainMemberVoter::LIST => 'true', DomainMemberVoter::VIEW => 'true']);

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

        $d = new ReflectionProperty(static::$container->get('unite.cms.manager'), 'domain');
        $d->setAccessible(true);
        $d->setValue(static::$container->get('unite.cms.manager'),$this->domain);
    }

    public function testGraphQLSchemaGenerationForEditor() {
        static::$container->get('security.token_storage')->setToken($this->users['editor']);
        $m = static::$container->get('unite.cms.graphql.schema_type_manager');
        $schema = $m->createSchema($this->domain, 'Query', 'Mutation');
        $this->assertEquals(['id', 'type', '_permissions', '_revisions', 'created', 'updated', 'deleted', 'f1', 'f2', 'f3', 'f5'], array_keys($m->getSchemaType('CtContent', $this->domain)->getFields()));
        $this->assertEquals(['f1', 'f2', 'f3', 'f5'], array_keys($m->getSchemaType('CtContentInput', $this->domain)->getFields()));

        $result = GraphQL::executeQuery(
            $schema,
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
            'f2' => null,
            'f4' => null,
            'f5' => null,
            'f6' => null,
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
            $schema,
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
        $this->assertEquals('F2', $result->data->updateCt->f2);
        $this->assertEquals('F5 Updated', $result->data->updateCt->f5);
        $this->assertNull($result->data->updateCt->f3);

        $getFullContent = $this->em->getRepository(Content::class)->find($result->data->updateCt->id);
        $this->assertEquals([
            'f1' => 'A',
            'f3' => 'F3 Updated',
            'f5' => 'F5 Updated',
            'f2' => 'F2',
            'f4' => 'F4',
        ], $getFullContent->getData());

        // Test accessing setting and domain member fields
        $this->settingType->getSetting()->setData(['f1' => 'F1']);
        $this->users['editor']->getUser()->getDomainMembers($this->domain)[0]->setData(['f1' => 'F1']);
        $this->em->flush();

        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                StSetting { f1 }
                findEditorMember { result { f1 } },
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals(null, $result->data->StSetting->f1);
        $this->assertEquals(null, $result->data->findEditorMember->result[0]->f1);

        $this->settingType->getSetting()->setData(['f1' => 'A']);
        $this->users['editor']->getUser()->getDomainMembers($this->domain)[0]->setData(['f1' => 'A']);
        $this->em->flush();

        $result = GraphQL::executeQuery(
            $schema,
            'query { 
                StSetting { f1 }
                findEditorMember { result { f1 } },
            }'
        );

        $result->setErrorFormatter(function (Error $error) {
            return UserErrorAtPath::createFormattedErrorFromException($error);
        });

        $result = json_decode(json_encode($result->toArray(true)));
        $this->assertEquals('A', $result->data->StSetting->f1);
        $this->assertEquals('A', $result->data->findEditorMember->result[0]->f1);
    }

    public function testGraphQLSchemaGenerationForViewer() {
        static::$container->get('security.token_storage')->setToken($this->users['viewer']);
        $m = static::$container->get('unite.cms.graphql.schema_type_manager');
        $schema = $m->createSchema($this->domain, 'Query', 'Mutation');
        $this->assertEquals(['id', 'type', '_permissions', '_revisions', 'created', 'updated', 'deleted', 'f1', 'f2', 'f3'], array_keys($m->getSchemaType('CtContent', $this->domain)->getFields()));
        $this->assertEquals(['f1', 'f2', 'f3'], array_keys($m->getSchemaType('CtContentInput', $this->domain)->getFields()));

        $this->assertEquals(['id', 'type', '_permissions', '_revisions', 'created', 'updated', '_name'], array_keys($m->getSchemaType('EditorMember', $this->domain)->getFields()));
        $this->assertEquals(['id', 'type', '_permissions', '_revisions', 'created', 'updated'], array_keys($m->getSchemaType('StSetting', $this->domain)->getFields()));

        $result = GraphQL::executeQuery(
            $schema,
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
            'f2' => null,
            'f4' => null,
            'f5' => null,
            'f6' => null,
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
            $schema,
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
        $this->assertEquals('F2', $result->data->updateCt->f2);
        $this->assertNull($result->data->updateCt->f3);

        $getFullContent = $this->em->getRepository(Content::class)->find($result->data->updateCt->id);
        $this->assertEquals([
            'f1' => 'A',
            'f3' => 'F3 Updated',
            'f5' => 'F5',
            'f2' => 'F2',
            'f4' => 'F4',
        ], $getFullContent->getData());
    }
}
