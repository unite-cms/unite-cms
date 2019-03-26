<?php

namespace UniteCMS\CoreBundle\Service;

use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use UniteCMS\CoreBundle\SchemaType\Types\CastEnum;

/**
 * Builds an doctrine query expression by evaluating a nested filter array:
 *
 * Examples:
 *
 * $filterInput = [ 'field' => 'id', 'operator' => '=', 'value' => 123 ];
 * $filterInput = [ 'AND' => [
 *   [ 'field' => 'id', 'operator' => '=', 'value' => 123 ],
 *   [ 'field' => 'id', 'operator' => '>', 'value' => 200 ]
 * ];
 * $filterInput = [ 'AND' => [
 *   'OR' => [
 *      ['field' => 'title', 'operator' => 'LIKE', 'value' => '%foo%'],
 *      ['field' => 'title', 'operator' => 'LIKE', 'value' => '%baa%'],
 *   ],
 *   [ 'field' => 'id', 'operator' => '=', 'value' => 123 ],
 * ]
 */
class GraphQLDoctrineFilterQueryBuilder
{
    private $contentEntityFields;
    private $contentEntityPrefix;

    private $filter = null;
    private $parameters = [];
    private $parameterCount = 0;
    private $expr;

    /**
     * GraphQLDoctrineFilterQueryBuilder constructor.
     * @param array $filterInput
     * @param array $contentEntityFields
     * @param string $contentEntityPrefix
     * @throws QueryException
     */
    public function __construct(array $filterInput, $contentEntityFields = [], $contentEntityPrefix)
    {
        $this->contentEntityFields = $contentEntityFields;
        $this->contentEntityPrefix = $contentEntityPrefix;
        $this->expr = new Expr();
        $this->filter = $this->getQueryBuilderComposite($filterInput);
    }

    /**
     * Returns the doctrine filter object.
     *
     * @return Comparison|Orx|Andx|string|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Returns all parameters, that where used in any filter.
     *
     * @return string[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * GraphQL does not allow to get mixed input values, so we always get a string. By providing a transform input
     * argument, we allow the user to use one of the defined transformation functions
     * @param string $value
     * @param string|null $transformation
     * @return mixed
     */
    protected function transformFilterValue(string $value = '', string $transformation = null) {
        switch ($transformation) {
            case CastEnum::CAST_INTEGER: return intval($value);
            case CastEnum::CAST_FLOAT: return floatval($value);
            case CastEnum::CAST_BOOLEAN: return boolval($value);
            case CastEnum::CAST_DATE: return is_numeric($value) ? date('Y-m-d', $value) : $value;
            case CastEnum::CAST_DATETIME: return is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;
            default: return $value;
        }
    }

    /**
     * Build the nested doctrine filter object.
     *
     * @param array $filterInput
     * @return Andx|Comparison|Orx|string
     * @throws QueryException
     */
    private function getQueryBuilderComposite(array $filterInput)
    {

        // filterInput can contain AND, OR or a direct expression

        if (!empty($filterInput['AND'])) {

            $filters = [];
            foreach ($filterInput['AND'] as $filter) {

                if (!is_array($filter)) {
                    throw new \InvalidArgumentException('AND operator expects an array of filters.');
                }

                $filters[] = $this->getQueryBuilderComposite($filter);
            }

            return new Andx($filters);
        } else {
            if (!empty($filterInput['OR'])) {

                $filters = [];
                foreach ($filterInput['OR'] as $filter) {

                    if (!is_array($filter)) {
                        throw new \InvalidArgumentException('OR operator expects an array of filters.');
                    }

                    $filters[] = $this->getQueryBuilderComposite($filter);
                }

                return new Orx($filters);
            } else {
                if (!empty($filterInput['operator']) && !empty($filterInput['field'])) {

                    $rightSide = null;
                    $parameter_name = null;

                    if (!empty($filterInput['value']) && !in_array(
                            $filterInput['operator'],
                            ['IS NULL', 'IS NOT NULL']
                        )) {
                        $this->parameterCount++;
                        $parameter_name = 'graphql_filter_builder_parameter'.$this->parameterCount;

                        $this->parameters[$parameter_name] = $this->transformFilterValue($filterInput['value'], $filterInput['cast']);
                        $rightSide = ':'.$parameter_name;
                    }

                    // if we filter by a content field.
                    if (in_array($filterInput['field'], $this->contentEntityFields)) {
                        $leftSide = $this->contentEntityPrefix.'.'.$filterInput['field'];

                        // if we filter by a nested content data field.
                    } else {
                        $leftSide = "JSON_EXTRACT(".$this->contentEntityPrefix.".data, '$.".$filterInput['field']."')";
                    }


                    // Support for special Operator, using ex Expr builder. This should be extended in the future.
                    switch ($filterInput['operator']) {
                        case 'IS NULL':
                            return $this->expr->isNull($leftSide);
                        case 'IS NOT NULL':
                            return $this->expr->isNotNull($leftSide);
                        case 'LIKE':
                            return $this->expr->like($leftSide, $rightSide);
                        case 'ILIKE':
                            return $this->expr->like($this->expr->lower($leftSide), $this->expr->lower($rightSide));
                        default:
                            if (in_array(
                                $filterInput['operator'],
                                [
                                    Comparison::EQ,
                                    Comparison::GT,
                                    Comparison::GTE,
                                    Comparison::LT,
                                    Comparison::LTE,
                                    Comparison::NEQ,
                                ]
                            )) {
                                return new Comparison($leftSide, $filterInput['operator'], $rightSide);
                            } else {
                                $expected = join(
                                    ',',
                                    [
                                        Comparison::EQ,
                                        Comparison::GT,
                                        Comparison::GTE,
                                        Comparison::LT,
                                        Comparison::LTE,
                                        Comparison::NEQ,
                                    ]
                                );
                                throw QueryException::syntaxError(
                                    "Invalid filter operator. Expected one of '{$expected}', got '".$filterInput['operator']."'"
                                );
                            }
                    }
                }
            }
        }

        return null;
    }

}
