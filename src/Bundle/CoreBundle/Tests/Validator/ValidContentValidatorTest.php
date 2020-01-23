<?php


namespace UniteCMS\CoreBundle\Tests\Validator;

use DateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\GraphQL\ErrorFormatter;
use UniteCMS\CoreBundle\Tests\Mock\TestContent;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;
use UniteCMS\CoreBundle\Validator\GenericContentValidatorConstraint;

class ValidContentValidatorTest extends SchemaAwareTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->buildSchema(
            '
            type Article implements UniteContent 
                @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true")
                @valid(if: "this.getFieldData(\'firstname\') == \'CREATE\'", groups: [ CREATE ], message: "INVALID CREATE!")
                @valid(if: "this.getFieldData(\'firstname\') == \'UPDATE\'", groups: [ UPDATE ], message: "INVALID UPDATE!")
                @valid(if: "this.getFieldData(\'firstname\') == \'DELETE\'", groups: [ DELETE ], message: "INVALID DELETE!")
                @valid(if: "this.getFieldData(\'firstname\') == \'RECOVER\'", groups: [ RECOVER ], message: "INVALID RECOVER!")
                @valid(if: "this.getFieldData(\'firstname\') == \'REVERT\'", groups: [ REVERT ], message: "INVALID REVERT!")
                @valid(if: "this.getFieldData(\'lastname\') and not this.getFieldData(\'lastname\').empty()", message: "INVALID ALL GROUPS!")
            {
                id: ID
                _meta: UniteContentMeta
                firstname: String @textField
                    @valid(if: "value and not value.empty()", message: "INVALID FIELD ALL GROUPS!")
                lastname: String @textField
                    @valid(if: "value == \'CREATE\'", groups: [ CREATE ])
                    @valid(if: "value == \'UPDATE\'", groups: [ UPDATE ])
                    @valid(if: "value == \'DELETE\'", groups: [ DELETE ])
                    @valid(if: "value == \'RECOVER\'", groups: [ RECOVER ])
                    @valid(if: "value == \'REVERT\'", groups: [ REVERT ])
            }
        ');
    }

    public function testContentValidationOnCreate()
    {
        $query = 'mutation($data: ArticleInput!) { createArticle(persist: true, data: $data) { firstname, lastname } }';

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['createArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'INVALID FIELD ALL GROUPS!',
                            'path' => '[firstname]',
                        ],
                        [
                            'message' => 'INVALID CREATE!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'data' => [],
        ], false);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['createArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID CREATE!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'data' => [
                'firstname' => 'foo',
                'lastname' => 'foo',
            ],
        ], false);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['createArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'data' => [
                'firstname' => 'CREATE',
                'lastname' => 'foo',
            ],
        ], false);

        $this->assertGraphQL([
            'createArticle' => [
                'firstname' => 'CREATE',
                'lastname' => 'CREATE',
            ],
        ], $query, [
            'data' => [
                'firstname' => 'CREATE',
                'lastname' => 'CREATE',
            ],
        ]);

    }

    public function testContentValidationOnUpdate()
    {
        $query = 'mutation($data: ArticleInput!, $id: ID!) { updateArticle(persist: true, data: $data, id: $id) { firstname, lastname } }';

        // Create test content
        $domain = static::$container->get(DomainManager::class)->current();
        $contentManager = $domain->getContentManager();
        $content = $contentManager->create($domain, 'Article');
        $contentManager->flush($domain);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['updateArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'INVALID FIELD ALL GROUPS!',
                            'path' => '[firstname]',
                        ],
                        [
                            'message' => 'INVALID UPDATE!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'id' => $content->getId(),
            'data' => [],
        ], false);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['updateArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID UPDATE!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'id' => $content->getId(),
            'data' => [
                'firstname' => 'CREATE',
                'lastname' => 'CREATE',
            ],
        ], false);

        $this->assertGraphQL([
            'updateArticle' => [
                'firstname' => 'UPDATE',
                'lastname' => 'UPDATE',
            ],
        ], $query, [
            'id' => $content->getId(),
            'data' => [
                'firstname' => 'UPDATE',
                'lastname' => 'UPDATE',
            ],
        ]);

    }

    public function testContentValidationOnDelete()
    {
        $query = 'mutation($id: ID!) { deleteArticle(persist: true, id: $id) { firstname, lastname } }';

        // Create test content
        $domain = static::$container->get(DomainManager::class)->current();
        $contentManager = $domain->getContentManager();
        $content = $contentManager->create($domain, 'Article');
        $contentManager->flush($domain);

        $dataMapper = static::$container->get(FieldDataMapper::class);

        $content->setData($dataMapper->mapToFieldData($domain, $content, []));

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['deleteArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'INVALID FIELD ALL GROUPS!',
                            'path' => '[firstname]',
                        ],
                        [
                            'message' => 'INVALID DELETE!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId()], false);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'UPDATE',
            'lastname' => 'UPDATE',
        ]));

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['deleteArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID DELETE!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId()], false);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'DELETE',
            'lastname' => 'DELETE',
        ]));

        $this->assertGraphQL([
            'deleteArticle' => [
                'firstname' => 'DELETE',
                'lastname' => 'DELETE',
            ],
        ], $query, ['id' => $content->getId()]);
    }

    public function testContentValidationOnRecover()
    {
        $query = 'mutation($id: ID!) { recoverArticle(persist: true, id: $id) { firstname, lastname } }';

        // Create test content
        $domain = static::$container->get(DomainManager::class)->current();
        $contentManager = $domain->getContentManager();
        $content = $contentManager->create($domain, 'Article');
        $contentManager->flush($domain);

        $content->setDeleted(new DateTime());

        $dataMapper = static::$container->get(FieldDataMapper::class);

        $content->setData($dataMapper->mapToFieldData($domain, $content, []));

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['recoverArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'INVALID FIELD ALL GROUPS!',
                            'path' => '[firstname]',
                        ],
                        [
                            'message' => 'INVALID RECOVER!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId()], false);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'DELETE',
            'lastname' => 'DELETE',
        ]));

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['recoverArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID RECOVER!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId()], false);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'RECOVER',
            'lastname' => 'RECOVER',
        ]));

        $this->assertGraphQL([
            'recoverArticle' => [
                'firstname' => 'RECOVER',
                'lastname' => 'RECOVER',
            ],
        ], $query, ['id' => $content->getId()]);
    }

    public function testContentValidationOnRevert()
    {
        $query = 'mutation($id: ID!, $v: Int!) { revertArticle(persist: true, id: $id, version: $v) { firstname, lastname } }';

        // Create test content
        $domain = static::$container->get(DomainManager::class)->current();
        $contentManager = $domain->getContentManager();
        $content = $contentManager->create($domain, 'Article');
        $contentManager->flush($domain);

        $dataMapper = static::$container->get(FieldDataMapper::class);

        $contentManager->update($domain, $content, $dataMapper->mapToFieldData($domain, $content, []));
        $contentManager->flush($domain);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['revertArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'INVALID FIELD ALL GROUPS!',
                            'path' => '[firstname]',
                        ],
                        [
                            'message' => 'INVALID REVERT!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId(), 'v' => 1], false);

        $contentManager->update($domain, $content, $dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'DELETE',
            'lastname' => 'DELETE',
        ]));
        $contentManager->flush($domain);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['revertArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'INVALID REVERT!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId(), 'v' => 2], false);

        $contentManager->update($domain, $content, $dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'REVERT',
            'lastname' => 'REVERT',
        ]));
        $contentManager->flush($domain);

        $this->assertGraphQL([
            'revertArticle' => [
                'firstname' => 'REVERT',
                'lastname' => 'REVERT',
            ],
        ], $query, ['id' => $content->getId(), 'v' => 3]);
    }

    public function testContentValidator() {
        $domain = static::$container->get(DomainManager::class)->current();
        $contentManager = $domain->getContentManager();
        $content = $contentManager->create($domain, 'Article');

        // First make sure, that the validator was registered.
        $testValidators = array_filter($domain->getContentTypeManager()->getContentType('Article')->getConstraints(), function(Constraint $constraint){
            return $constraint instanceof GenericContentValidatorConstraint;
        });
        $this->assertCount(1, $testValidators);

        // Validate content with special data key.
        $content = new TestContent('Article', [
            'firstname' => new FieldData('CREATE'),
            'lastname' => new FieldData('CREATE'),
            'test_global_validator' => new FieldData('foo')
        ]);
        $violations = static::$container->get(ValidatorInterface::class)->validate($content, null, [Constraint::DEFAULT_GROUP, 'CREATE']);
        $this->assertCount(1, $violations);
        $this->assertEquals('foo', $violations->get(0)->getMessage());

        $content = new TestContent('Article', [
            'firstname' => new FieldData('CREATE'),
            'lastname' => new FieldData('CREATE'),
        ]);
        $violations = static::$container->get(ValidatorInterface::class)->validate($content, null, ['CREATE']);
        $this->assertCount(0, $violations);
    }
}
