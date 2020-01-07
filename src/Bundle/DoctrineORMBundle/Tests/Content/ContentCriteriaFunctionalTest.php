<?php


namespace UniteCMS\DoctrineORMBundle\Tests\Content;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\ReferenceFieldData;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;
use UniteCMS\CoreBundle\Query\BaseFieldOrderBy;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\DataFieldComparison;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldComparison;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldOrderBy;
use UniteCMS\DoctrineORMBundle\Tests\DatabaseAwareTestCase;

class ContentCriteriaFunctionalTest extends DatabaseAwareTestCase
{
    const TEST_SCHEMA = '
        type TestContentA implements UniteContent {
            id: ID
            _meta: UniteContentMeta
            a_text: String @textField
            a_bool: Boolean @booleanField
            a_int: Int @integerField
            a_float: Float @floatField
            a_reference: TestContentB @referenceField
        }
        
        type TestContentB implements UniteContent {
            id: ID
            _meta: UniteContentMeta
            b_text: String @textField
            b_bool: Boolean @booleanField
        }
    ';

    protected $content = [];

    public function setUp()
    {
        parent::setUp();

        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getContentManager();
        $this->buildSchema(static::TEST_SCHEMA);

        $this->content['A1'] = $manager->create($domain, 'TestContentA');
        $this->content['A2'] = $manager->create($domain, 'TestContentA');
        $this->content['B1'] = $manager->create($domain, 'TestContentB');
        $this->content['B2'] = $manager->create($domain, 'TestContentB');

        $manager->flush($domain);

        $manager->update($domain, $this->content['A1'], [
            'a_text' => new FieldData('Foo'),
            'a_bool' => new FieldData(true),
            'a_reference' => new ReferenceFieldData($this->content['B1']->getId()),
            'a_int' => new FieldData(10),
            'a_float' => new FieldData(10.90),
        ]);

        $manager->update($domain, $this->content['A2'], [
            'a_text' => new FieldData('Baa'),
            'a_bool' => new FieldData(false),
            'a_int' => new FieldData(9),
            'a_float' => new FieldData(10.10),
        ]);

        $manager->update($domain, $this->content['B1'], [
            'b_text' => new FieldData('Luu'),
            'b_bool' => new FieldData(true),
        ]);

        $manager->update($domain, $this->content['B2'], [
            'b_text' => new FieldData('Laa'),
            'b_bool' => new FieldData(null),
        ]);

        $manager->flush($domain);
    }

    public function testContentCriteria() {

        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getContentManager();
        $criteria = new ContentCriteria();

        // Find all content
        $this->assertEquals([
            $this->content['A1'],
            $this->content['A2'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with title Baa
        $criteria = new ContentCriteria(new DataFieldComparison('a_text', DataFieldComparison::EQ, 'Baa'));
        $this->assertEquals([
            $this->content['A2'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with title Baa
        $criteria = new ContentCriteria(new DataFieldComparison('a_text', DataFieldComparison::CONTAINS, 'ba'));
        $this->assertEquals([
            $this->content['A2'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with title Baa
        $criteria = new ContentCriteria(new DataFieldComparison('a_text', ContentCriteria::NCONTAINS, 'ba'));
        $this->assertEquals([
            $this->content['A1'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with title Baa or bool true
        $criteria = new ContentCriteria(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new DataFieldComparison('a_text', DataFieldComparison::EQ, 'Baa'),
            new DataFieldComparison('a_bool', DataFieldComparison::EQ, true)
        ]));
        $this->assertEquals([
            $this->content['A1'],
            $this->content['A2'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with bool true
        $criteria = new ContentCriteria(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new DataFieldComparison('a_bool', DataFieldComparison::EQ, true)
        ]));
        $this->assertEquals([
            $this->content['A1'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with bool not null
        $criteria = new ContentCriteria(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new DataFieldComparison('a_bool', DataFieldComparison::NEQ, null)
        ]));
        $this->assertEquals([
            $this->content['A1'],
            $this->content['A2'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with base fields
        $criteria = new ContentCriteria(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new BaseFieldComparison('id', DataFieldComparison::EQ, $this->content['A1']->getId())
        ]));
        $this->assertEquals([
            $this->content['A1'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());

        // Find content with base fields
        $criteria = new ContentCriteria(new CompositeExpression(CompositeExpression::TYPE_OR, [
            new BaseFieldComparison('updated', DataFieldComparison::NEQ, null)
        ]));
        $this->assertEquals([
            $this->content['A1'],
            $this->content['A2'],
        ], $manager->find($domain, 'TestContentA', $criteria)->getResult());
    }

    public function testReferencedContentCriteria()
    {
        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getContentManager();

        // Find content with a reference
        $criteria = new ContentCriteria(new DataFieldComparison('a_reference', DataFieldComparison::NEQ, null));
        $this->assertEquals(
            [
                $this->content['A1'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Find content with a special reference
        $criteria = new ContentCriteria(new DataFieldComparison('a_reference', DataFieldComparison::EQ, $this->content['B1']->getId()));
        $this->assertEquals(
            [
                $this->content['A1'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Find content with a referenced base field
        $criteria = new ContentCriteria(new ReferenceDataFieldComparison(
            'a_reference',
            DataFieldComparison::EQ,
            $this->content['B1']->getId(),
            ['data'],
            'TestContentB', ['id']));
        $this->assertEquals(
            [
                $this->content['A1'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Find content with a referenced data field
        $criteria = new ContentCriteria(new ReferenceDataFieldComparison(
            'a_reference',
            DataFieldComparison::EQ,
            'Luu',
            ['data'],
            'TestContentB', ['b_text']));
        $this->assertEquals(
            [
                $this->content['A1'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Find content with an empty referenced data field
        $criteria = new ContentCriteria(new ReferenceDataFieldComparison(
            'a_reference',
            DataFieldComparison::EQ,
            null,
            ['data'],
            'TestContentB', ['foo_baa']));
        $this->assertEquals(
            [
                $this->content['A1'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );
    }

    public function testOrderBy()
    {
        $domain = static::$container->get(DomainManager::class)->current();
        $manager = $domain->getContentManager();
        $criteria = new ContentCriteria();

        // Order by base field
        $criteria->orderBy(new BaseFieldOrderBy('created', 'ASC'));
        $this->assertEquals(
            [
                $this->content['A1'],
                $this->content['A2'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Order by data field
        $criteria->orderBy(new DataFieldOrderBy('a_text', 'DESC'));
        $this->assertEquals(
            [
                $this->content['A1'],
                $this->content['A2'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Order by boolean field
        $criteria->orderBy(new DataFieldOrderBy('a_bool', 'DESC'));
        $this->assertEquals(
            [
                $this->content['A1'],
                $this->content['A2'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Order by int field
        $criteria->orderBy(new DataFieldOrderBy('a_int', 'DESC'));
        $this->assertEquals(
            [
                $this->content['A1'],
                $this->content['A2'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Order by float field
        $criteria->orderBy(new DataFieldOrderBy('a_float', 'DESC'));
        $this->assertEquals(
            [
                $this->content['A1'],
                $this->content['A2'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );

        // Order by referenced field
        $criteria->orderBy(new ReferenceDataFieldOrderBy('a_reference', 'b_text', 'ASC'));
        $this->assertEquals(
            [
                $this->content['A1'],
            ],
            $manager->find($domain, 'TestContentA', $criteria)->getResult()
        );
    }
}
