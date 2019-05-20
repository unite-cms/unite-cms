<?php

namespace UniteCMS\CoreBundle\Tests\Service;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;

class GraphQLDoctrineFilterQueryBuilderTest extends TestCase
{
    public function testBuildingEmpty() {
        $builder = new GraphQLDoctrineFilterQueryBuilder([], ['id', 'locale'], 'c');

        $this->assertEquals([], $builder->getParameters());
        $this->assertEquals(null, $builder->getFilter());
    }

    public function testBuildingSimpleORandANDFilter() {

        $builder = new GraphQLDoctrineFilterQueryBuilder(['field' => 'id', 'operator' => '=', 'value' => 123], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter_'.spl_object_id($builder).'_1' => 123], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Comparison::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter_".spl_object_id($builder)."_1", (string)$filter);

        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'OR' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['field' => 'any_field', 'operator' => 'LIKE', 'value' => '%value%']
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter_'.spl_object_id($builder).'_1' => 123, 'graphql_filter_builder_parameter_'.spl_object_id($builder).'_2' => '%value%'], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Orx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter_".spl_object_id($builder)."_1 OR JSON_EXTRACT(c.data, '$.any_field') LIKE :graphql_filter_builder_parameter_".spl_object_id($builder)."_2", (string)$filter);


        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'AND' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['field' => 'any_field', 'operator' => 'LIKE', 'value' => '%value%']
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter_'.spl_object_id($builder).'_1' => 123, 'graphql_filter_builder_parameter_'.spl_object_id($builder).'_2' => '%value%'], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Andx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter_".spl_object_id($builder)."_1 AND JSON_EXTRACT(c.data, '$.any_field') LIKE :graphql_filter_builder_parameter_".spl_object_id($builder)."_2", (string)$filter);

        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'OR' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['field' => 'any_field', 'operator' => 'ILIKE', 'value' => '%value%']
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter_'.spl_object_id($builder).'_1' => 123, 'graphql_filter_builder_parameter_'.spl_object_id($builder).'_2' => '%value%'], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Orx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter_".spl_object_id($builder)."_1 OR LOWER(JSON_EXTRACT(c.data, '$.any_field')) LIKE LOWER(:graphql_filter_builder_parameter_".spl_object_id($builder)."_2)", (string)$filter);


        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'AND' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['field' => 'any_field', 'operator' => 'ILIKE', 'value' => '%value%']
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals(['graphql_filter_builder_parameter_'.spl_object_id($builder).'_1' => 123, 'graphql_filter_builder_parameter_'.spl_object_id($builder).'_2' => '%value%'], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Andx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter_".spl_object_id($builder)."_1 AND LOWER(JSON_EXTRACT(c.data, '$.any_field')) LIKE LOWER(:graphql_filter_builder_parameter_".spl_object_id($builder)."_2)", (string)$filter);
    }

    public function testBuildingComplexNestedFilter() {

        $builder = new GraphQLDoctrineFilterQueryBuilder([
            'AND' => [
                ['field' => 'id', 'operator' => '=', 'value' => 123],
                ['OR' => [
                    ['field' => 'locale', 'operator' => 'LIKE', 'value' => '%foo%'],
                    ['field' => 'locale', 'operator' => 'ILIKE', 'value' => '%baa%'],
                    ['AND' => [
                        ['field' => 'locale', 'operator' => 'LIKE', 'value' => '%foo2%'],
                        ['field' => 'locale', 'operator' => 'ILIKE', 'value' => '%baa2%'],
                    ]]
                ]]
            ]
        ], ['id', 'locale'], 'c');

        $this->assertEquals([
            'graphql_filter_builder_parameter_'.spl_object_id($builder).'_1' => 123,
            'graphql_filter_builder_parameter_'.spl_object_id($builder).'_2' => '%foo%',
            'graphql_filter_builder_parameter_'.spl_object_id($builder).'_3' => '%baa%',
            'graphql_filter_builder_parameter_'.spl_object_id($builder).'_4' => '%foo2%',
            'graphql_filter_builder_parameter_'.spl_object_id($builder).'_5' => '%baa2%',
        ], $builder->getParameters());
        $filter = $builder->getFilter();
        $this->assertInstanceOf(Andx::class, $filter);
        $this->assertEquals("c.id = :graphql_filter_builder_parameter_".spl_object_id($builder)."_1 AND (c.locale LIKE :graphql_filter_builder_parameter_".spl_object_id($builder)."_2 OR LOWER(c.locale) LIKE LOWER(:graphql_filter_builder_parameter_".spl_object_id($builder)."_3) OR (c.locale LIKE :graphql_filter_builder_parameter_".spl_object_id($builder)."_4 AND LOWER(c.locale) LIKE LOWER(:graphql_filter_builder_parameter_".spl_object_id($builder)."_5)))", (string)$filter);
    }
}
