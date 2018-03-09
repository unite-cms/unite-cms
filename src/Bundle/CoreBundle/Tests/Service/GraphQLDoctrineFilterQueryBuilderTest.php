<?php

namespace UnitedCMS\CoreBundle\Tests\Service;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use PHPUnit\Framework\TestCase;
use UnitedCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;

class GraphQLDoctrineFilterQueryBuilderTest extends TestCase
{
    public function testBuildingEmpty() {
        $builder = new GraphQLDoctrineFilterQueryBuilder([], ['id', 'locale'], 'c');

        $this->assertEquals([], $builder->getParameters());
        $this->assertEquals(null, $builder->getFilter());
    }

    public function testBuildingSimpleORandANDFilter() {

        $builder = new GraphQLDoctrineFilterQueryBuilder(['field' => 'id', 'operator' => '=', 'value' => 123], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter1' => 123], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Comparison::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter1", (string)$filter);

        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'OR' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['field' => 'any_field', 'operator' => 'LIKE', 'value' => '%value%']
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter1' => 123, 'graphql_filter_builder_parameter2' => '%value%'], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Orx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter1 OR JSON_EXTRACT(c.data, '$.any_field') LIKE :graphql_filter_builder_parameter2", (string)$filter);


        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'AND' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['field' => 'any_field', 'operator' => 'LIKE', 'value' => '%value%']
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter1' => 123, 'graphql_filter_builder_parameter2' => '%value%'], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Andx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter1 AND JSON_EXTRACT(c.data, '$.any_field') LIKE :graphql_filter_builder_parameter2", (string)$filter);
    }

    public function testBuildingComplexNestedFilter() {

        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'AND' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['OR' => [
                    ['field' => 'locale', 'operator' => 'LIKE', 'value' => '%foo%'],
                    ['field' => 'locale', 'operator' => 'LIKE', 'value' => '%baa%'],
                    ['AND' => [
                        ['field' => 'locale', 'operator' => 'LIKE', 'value' => '%foo2%'],
                        ['field' => 'locale', 'operator' => 'LIKE', 'value' => '%baa2%'],
                    ]]
                ]]
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals([
            'graphql_filter_builder_parameter1' => 123,
            'graphql_filter_builder_parameter2' => '%foo%',
            'graphql_filter_builder_parameter3' => '%baa%',
            'graphql_filter_builder_parameter4' => '%foo2%',
            'graphql_filter_builder_parameter5' => '%baa2%',
        ], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Andx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter1 AND (c.locale LIKE :graphql_filter_builder_parameter2 OR c.locale LIKE :graphql_filter_builder_parameter3 OR (c.locale LIKE :graphql_filter_builder_parameter4 AND c.locale LIKE :graphql_filter_builder_parameter5))", (string)$filter);
    }
}