<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.10.18
 * Time: 14:09
 */

namespace UniteCMS\CoreBundle\Tests\View;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Service\ReferenceResolver;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;
use UniteCMS\CoreBundle\View\ViewSettings;

class TreeViewTypeTest extends ContainerAwareTestCase
{
    /**
     * @var View $view
     */
    private $view;

    /**
     * @var ViewSettings $viewSettings
     */
    private $viewSettings;

    /**
     * @var ReferenceResolver $referenceResolverMock
     */
    private $referenceResolverMock;

    public function setUp()
    {
        parent::setUp();
        $this->viewSettings = new ViewSettings();
        $this->view = new View();
        $this->view
            ->setType('tree')
            ->setTitle('New View')
            ->setIdentifier('new_view')
            ->setSettings($this->viewSettings)
            ->setContentType(new ContentType())
            ->getContentType()
                ->setTitle('Comments')
                ->setIdentifier('comments')
                ->setDomain(new Domain())
                ->getDomain()
                    ->setTitle('D1')
                    ->setIdentifier('d1')
                    ->setOrganization(new Organization())
                        ->getOrganization()
                            ->setTitle('O1')
                            ->setIdentifier('o1');

        $domain = $this->view->getContentType()->getDomain();

        // Add another content type
        $news = new ContentType();
        $news->setTitle('News')->setIdentifier('news');
        $domain->addContentType($news);

        // Create a reference field from comments to news
        $news_ref = new ContentTypeField();
        $news_ref
            ->setIdentifier('news_ref')
            ->setTitle('News Ref')
            ->setType('reference')
            ->setSettings(new FieldableFieldSettings([
                'domain' => 'd1',
                'content_type' => 'news',
            ]));
        $this->view->getContentType()->addField($news_ref);

        // Create a reference_of field from news to comments
        $news_ref_of = new ContentTypeField();
        $news_ref_of
            ->setIdentifier('news_ref_of')
            ->setTitle('News Ref Of')
            ->setType('reference_of')
            ->setSettings(new FieldableFieldSettings([
                'domain' => 'd1',
                'content_type' => 'comments',
                'reference_field' => 'news_ref',
            ]));
        $news->addField($news_ref_of);




        // Add another content type
        $votes = new ContentType();
        $votes->setTitle('Votes')->setIdentifier('votes');
        $domain->addContentType($votes);

        // Create a reference field from votes to comments
        $vote_comment_ref = new ContentTypeField();
        $vote_comment_ref
            ->setIdentifier('comment_ref')
            ->setTitle('Comment ref')
            ->setType('reference')
            ->setSettings(new FieldableFieldSettings([
                'domain' => 'd1',
                'content_type' => 'comments',
            ]));
        $votes->addField($vote_comment_ref);

        // Create a reference_of field from comments to votes
        $vote_comment_ref_of = new ContentTypeField();
        $vote_comment_ref_of
            ->setIdentifier('comment_ref_of')
            ->setTitle('Comment Ref Of')
            ->setType('reference_of')
            ->setSettings(new FieldableFieldSettings([
                'domain' => 'd1',
                'content_type' => 'votes',
                'reference_field' => 'comment_ref',
            ]));
        $this->view->getContentType()->addField($vote_comment_ref_of);




        // Create a comment title field
        $title = new ContentTypeField();
        $title->setType('text')->setTitle('Title')->setIdentifier('title');
        $this->view->getContentType()->addField($title);

        // Create parent_comment and child_comments fields
        $parent_comment = new ContentTypeField();
        $parent_comment
            ->setIdentifier('parent_comment')
            ->setTitle('Parent comment')
            ->setType('reference')
            ->setSettings(new FieldableFieldSettings([
                'domain' => 'd1',
                'content_type' => 'comments',
            ]));
        $this->view->getContentType()->addField($parent_comment);

        $child_comments = new ContentTypeField();
        $child_comments
            ->setIdentifier('child_comments')
            ->setTitle('Child comments')
            ->setType('reference_of')
            ->setSettings(new FieldableFieldSettings([
                'domain' => 'd1',
                'content_type' => 'comments',
                'reference_field' => 'parent_comment',
            ]));
        $this->view->getContentType()->addField($child_comments);

        $this->referenceResolverMock = $this->getMockBuilder(ReferenceResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['resolveDomain', 'setFallbackFromContext'])
            ->getMock();
        $this->referenceResolverMock
            ->method('resolveDomain')
            ->willReturn($domain);

        $o01 = new \ReflectionProperty(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference'), 'referenceResolver');
        $o01->setAccessible(true);
        $o01->setValue(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference'), $this->referenceResolverMock);

        $o01 = new \ReflectionProperty(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference'), 'referenceResolver');
        $o01->setAccessible(true);
        $o01->setValue(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference'), $this->referenceResolverMock);

        $o01 = new \ReflectionProperty(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference_of'), 'referenceResolver');
        $o01->setAccessible(true);
        $o01->setValue(static::$container->get('unite.cms.field_type_manager')->getFieldType('reference_of'), $this->referenceResolverMock);
    }


    public function testTreeView()
    {
        // A tree view without a "children" field is not valid
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('The child node "children_field" at path "settings" must be configured.', $errors->get(0)->getMessage());

        // A tree view without a scalar "children" field is not valid
        $this->view->setSettings(new ViewSettings(['children_field' => ['foo' => 'baa']]));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.children_field". Expected scalar, but got array.', $errors->get(0)->getMessage());

        // A tree view without a known field is not valid
        $this->view->setSettings(new ViewSettings(['children_field' => 'foo']));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Field "foo" does not exist.', $errors->get(0)->getMessage());

        // A tree view with a "children" field, that does not reference itself is not valid.
        $this->view->setSettings(new ViewSettings(['children_field' => 'title']));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Field "title" is of type "text" but must be of type "reference_of".', $errors->get(0)->getMessage());

        $this->view->setSettings(new ViewSettings(['children_field' => 'news_ref']));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Field "news_ref" is of type "reference" but must be of type "reference_of".', $errors->get(0)->getMessage());

        $this->view->setSettings(new ViewSettings(['children_field' => 'comment_ref_of']));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Field "comment_ref_of" must reference itself.', $errors->get(0)->getMessage());



        // A tree view with a valid "children" field is valid.
        $this->view->setSettings(new ViewSettings(['children_field' => 'child_comments']));
        $this->assertCount(0, static::$container->get('validator')->validate($this->view));


        // A tree view with a non-scalar "rows_per_page" field is not valid
        $this->view->setSettings(new ViewSettings(['children_field' => 'child_comments', 'rows_per_page' => ['foo' => 'baa']]));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid type for path "settings.rows_per_page". Expected scalar, but got array.', $errors->get(0)->getMessage());

        // A tree view with a string "rows_per_page" field is not valid
        $this->view->setSettings(new ViewSettings(['children_field' => 'child_comments', 'rows_per_page' => '20']));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid rows_per_page configuration - must be an integer', $errors->get(0)->getMessage());

        // A tree view with a float "rows_per_page" field is not valid
        $this->view->setSettings(new ViewSettings(['children_field' => 'child_comments', 'rows_per_page' => 20.5]));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid rows_per_page configuration - must be an integer', $errors->get(0)->getMessage());

        // A tree view with an integer "rows_per_page" field is valid
        $this->view->setSettings(new ViewSettings(['children_field' => 'child_comments', 'rows_per_page' => 20]));
        $errors = static::$container->get('validator')->validate($this->view);
        $this->assertCount(0, static::$container->get('validator')->validate($this->view));


        // Test templateRenderParameters.
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($this->view);
        $this->assertTrue($parameters->isSelectModeNone());

        $fields = $parameters->get('fields');
        $fields['child_comments']['settings']['fields']['child_comments']['settings']['fields'] = [];
        $this->assertEquals(
            [
                'id' => [
                    'label' => 'Id',
                    'type' => 'id'
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text'
                ],
                'created' => [
                    'label' => 'Created',
                    'type' => 'date'
                ],
                'updated' => [
                    'label' => 'Updated',
                    'type' => 'date'
                ],
                'child_comments' => [
                    'type' => 'tree_view_children',
                    'settings' => [
                        'fields' => [
                            'id' => [
                                'label' => 'Id',
                                'type' => 'id'
                            ],
                            'title' => [
                                'label' => 'Title',
                                'type' => 'text'
                            ],
                            'created' => [
                                'label' => 'Created',
                                'type' => 'date'
                            ],
                            'updated' => [
                                'label' => 'Updated',
                                'type' => 'date'
                            ],
                            'child_comments' => [
                                'type' => 'tree_view_children',
                                'settings' => [
                                    'fields' => [],
                                    'sort' => [
                                        'field' => 'updated',
                                        'asc' => false,
                                    ],
                                    'filter' => null,
                                ],
                            ],
                        ],
                        'sort' => [
                            'field' => 'updated',
                            'asc' => false,
                        ],
                        'filter' => null,
                    ],
                ],
            ],
            $fields
        );
        $this->assertEquals('child_comments', $parameters->get('children_field'));
        $this->assertEquals('parent_comment', $parameters->get('parent_field'));


        // Test templateRenderParameters.
        $filter = ['AND' => [['field' => 'f1', 'operator' => '=', 'value' => '1']]];
        $this->view->setSettings(new ViewSettings(['children_field' => 'child_comments', 'rows_per_page' => 20, 'filter' => $filter]));
        $parameters = static::$container->get('unite.cms.view_type_manager')->getTemplateRenderParameters($this->view);
        $this->assertTrue($parameters->isSelectModeNone());

        $fields = $parameters->get('fields');
        $fields['child_comments']['settings']['fields']['child_comments']['settings']['fields'] = [];
        $this->assertEquals(
            [
                'id' => [
                    'label' => 'Id',
                    'type' => 'id'
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'text'
                ],
                'created' => [
                    'label' => 'Created',
                    'type' => 'date'
                ],
                'updated' => [
                    'label' => 'Updated',
                    'type' => 'date'
                ],
                'child_comments' => [
                    'type' => 'tree_view_children',
                    'settings' => [
                        'fields' => [
                            'id' => [
                                'label' => 'Id',
                                'type' => 'id'
                            ],
                            'title' => [
                                'label' => 'Title',
                                'type' => 'text'
                            ],
                            'created' => [
                                'label' => 'Created',
                                'type' => 'date'
                            ],
                            'updated' => [
                                'label' => 'Updated',
                                'type' => 'date'
                            ],
                            'child_comments' => [
                                'type' => 'tree_view_children',
                                'settings' => [
                                    'fields' => [],
                                    'sort' => [
                                        'field' => 'updated',
                                        'asc' => false,
                                    ],
                                    'filter' => $filter,
                                ],
                            ],
                        ],
                        'sort' => [
                            'field' => 'updated',
                            'asc' => false,
                        ],
                        'filter' => $filter,
                    ],
                ],
            ],
            $fields
        );
    }
}