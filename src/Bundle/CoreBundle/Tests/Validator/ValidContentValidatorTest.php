<?php


namespace UniteCMS\CoreBundle\Tests\Validator;

use DateTime;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\GraphQL\ErrorFormatter;
use UniteCMS\CoreBundle\Tests\SchemaAwareTestCase;

class ValidContentValidatorTest extends SchemaAwareTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->buildSchema(
            '
            type Article implements UniteContent 
                @access(query: "true", mutation: "true", create: "true", read: "true", update: "true", delete: "true")
                @valid(if: "this.getFieldData(\'firstname\') == \'CREATE\'", groups: [ CREATE ], message: "Invalid CREATE!")
                @valid(if: "this.getFieldData(\'firstname\') == \'UPDATE\'", groups: [ UPDATE ], message: "Invalid UPDATE!")
                @valid(if: "this.getFieldData(\'firstname\') == \'DELETE\'", groups: [ DELETE ], message: "Invalid DELETE!")
                @valid(if: "this.getFieldData(\'firstname\') == \'RECOVER\'", groups: [ RECOVER ], message: "Invalid RECOVER!")
                @valid(if: "this.getFieldData(\'firstname\') == \'REVERT\'", groups: [ REVERT ], message: "Invalid REVERT!")
                @valid(if: "this.getFieldData(\'lastname\') and not this.getFieldData(\'lastname\').empty()", message: "Invalid ALL GROUPS!")
            {
                id: ID
                _meta: UniteContentMeta!
                firstname: String @textField
                lastname: String @textField
                    @valid(if: "value == \'CREATE\'", groups: [ CREATE ])
                    @valid(if: "value == \'UPDATE\'", groups: [ UPDATE ])
                    @valid(if: "value == \'DELETE\'", groups: [ DELETE ])
                    @valid(if: "value == \'RECOVER\'", groups: [ RECOVER ])
                    @valid(if: "value == \'REVERT\'", groups: [ REVERT ])
                    @valid(if: "value and not value.empty()")
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
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid CREATE!',
                            'path' => '',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'data' => [
                'firstname' => 'foo',
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
                        [
                            'message' => 'Invalid CREATE!',
                            'path' => '',
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
        $contentManager->persist($domain, $content, ContentEvent::CREATE);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['updateArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid UPDATE!',
                            'path' => '',
                        ],
                    ],
                ],
            ]
        ], $query, [
            'id' => $content->getId(),
            'data' => [
                'firstname' => 'foo',
            ],
        ], false);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['updateArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid UPDATE!',
                            'path' => '',
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
        $contentManager->persist($domain, $content, ContentEvent::CREATE);

        $dataMapper = static::$container->get(FieldDataMapper::class);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'foo',
        ]));

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['deleteArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid DELETE!',
                            'path' => '',
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
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid DELETE!',
                            'path' => '',
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
        $contentManager->persist($domain, $content, ContentEvent::CREATE);

        $content->setDeleted(new DateTime());

        $dataMapper = static::$container->get(FieldDataMapper::class);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'foo',
        ]));

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['recoverArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid RECOVER!',
                            'path' => '',
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
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid RECOVER!',
                            'path' => '',
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
        $contentManager->persist($domain, $content, ContentEvent::CREATE);

        $dataMapper = static::$container->get(FieldDataMapper::class);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'foo',
        ]));
        $contentManager->persist($domain, $content, ContentEvent::UPDATE);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['revertArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid ALL GROUPS!',
                            'path' => '',
                        ],
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid REVERT!',
                            'path' => '',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId(), 'v' => 1], false);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'DELETE',
            'lastname' => 'DELETE',
        ]));
        $contentManager->persist($domain, $content, ContentEvent::UPDATE);

        $this->assertGraphQL([
            [
                'message' => ErrorFormatter::VALIDATION_MESSAGE,
                'path' => ['revertArticle'],
                'extensions' => [
                    'category' => ErrorFormatter::VALIDATION_CATEGORY,
                    'violations' => [
                        [
                            'message' => 'This value is not valid.',
                            'path' => '[lastname]',
                        ],
                        [
                            'message' => 'Invalid REVERT!',
                            'path' => '',
                        ],
                    ],
                ],
            ]
        ], $query, ['id' => $content->getId(), 'v' => 2], false);

        $content->setData($dataMapper->mapToFieldData($domain, $content, [
            'firstname' => 'REVERT',
            'lastname' => 'REVERT',
        ]));
        $contentManager->persist($domain, $content, ContentEvent::UPDATE);

        $this->assertGraphQL([
            'revertArticle' => [
                'firstname' => 'REVERT',
                'lastname' => 'REVERT',
            ],
        ], $query, ['id' => $content->getId(), 'v' => 3]);
    }
}
