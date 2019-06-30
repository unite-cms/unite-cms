<?php

namespace UniteCMS\CoreBundle\Service;

use Doctrine\ORM\Query\Expr\Join;
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
    private $parameter_prefix = null;

    private $contentEntityFields;
    private $contentEntityPrefix;

    private $filter = null;
    private $parameters = [];

    /**
     * @var \Closure $alterFilterInput
     */
    private $alterFilterInput = null;

    /**
     * @var Join[] $availableJoins
     */
    private $availableJoins = [];

    /**
     * @var Join[] $joins
     */
    private $joins = [];

    private $parameterCount = 0;
    private $expr;

    /**
     * GraphQLDoctrineFilterQueryBuilder constructor.
     * @param array $filterInput
     * @param array $contentEntityFields
     * @param string $contentEntityPrefix
     * @param array $availableJoins
     * @param \Closure|null $alterFilterInput
     * @throws QueryException
     */
    public function __construct(array $filterInput, $contentEntityFields = [], $contentEntityPrefix, $availableJoins = [], $alterFilterInput = null)
    {
        $this->parameter_prefix = spl_object_id($this);
        $this->filterInput = $filterInput;
        $this->contentEntityFields = $contentEntityFields;
        $this->contentEntityPrefix = $contentEntityPrefix;
        $this->availableJoins = $availableJoins;
        $this->alterFilterInput = $alterFilterInput;
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
     * Returns all joins, that where added during filter generation.
     *
     * @return Join[]
     */
    public function getJoins()
    {
        return $this->joins;
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
            case CastEnum::CAST_BOOLEAN: return is_numeric($value) ? boolval($value) : filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case CastEnum::CAST_DATE: return is_numeric($value) ? date('Y-m-d', $value) : $value;
            case CastEnum::CAST_DATETIME: return is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;
            default: return $value;
        }
    }

    /**
     * Return a dql where statement for the given operator and left side & right side
     * @param string $operator
     * @param string $leftSide
     * @param null $rightSide
     * @return Andx|Comparison|Orx|string
     * @throws QueryException
     */
    protected function getWhereStatement(string $operator, $leftSide, $rightSide = null) {

        // Support for special Operator, using ex Expr builder. This should be extended in the future.
        switch ($operator) {
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
                    $operator,
                    [
                        Comparison::EQ,
                        Comparison::GT,
                        Comparison::GTE,
                        Comparison::LT,
                        Comparison::LTE,
                        Comparison::NEQ,
                    ]
                )) {
                    return new Comparison($leftSide, $operator, $rightSide);
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
                        "Invalid filter operator. Expected one of '{$expected}', got '{$operator}'"
                    );
                }
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
        if($this->alterFilterInput) {
            $func = $this->alterFilterInput;
            $filterInput = $func($filterInput);
        }

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

                    // If this is a join field.
                    $fieldParts = explode('.', $filterInput['field']);
                    if(count($fieldParts) > 1) {
                        $rootField = array_shift($fieldParts);

                        foreach($this->availableJoins as $join) {
                            if($rootField === $join->getAlias()) {

                                $filterInput['field'] = implode('.', $fieldParts);

                                // For the moment we only allow to add one level of nested joined filters.
                                $joinedFilter = new GraphQLDoctrineFilterQueryBuilder($filterInput, ['name'], $join->getAlias(), [], $this->alterFilterInput);
                                $this->joins[] = $join;
                                $this->parameters = array_merge($this->parameters, $joinedFilter->getParameters());
                                $this->parameterCount += count($joinedFilter->getParameters());
                                return $joinedFilter->getFilter();
                            }
                        }
                    }

                    if ((!empty($filterInput['value']) || (isset($filterInput['value']) && $filterInput['value'] === '0')) && !in_array(
                            $filterInput['operator'],
                            ['IS NULL', 'IS NOT NULL']
                        )) {
                        $this->parameterCount++;
                        $parameter_name = 'graphql_filter_builder_parameter_'.$this->parameter_prefix.'_'.$this->parameterCount;

                        $this->parameters[$parameter_name] = $this->transformFilterValue($filterInput['value'], $filterInput['cast'] ?? null);
                        $rightSide = ':'.$parameter_name;
                    }

                    // if we filter by a content field.
                    if (in_array($filterInput['field'], $this->contentEntityFields)) {
                        $leftSide = $this->contentEntityPrefix.'.'.$filterInput['field'];

                    // if we filter by a nested content data field.
                    } else {
                        $leftSide = "JSON_EXTRACT(".$this->contentEntityPrefix.".data, '$.".$filterInput['field']."')";
                    }

                    // This is a little hack, because MySQL PDO is transmitting boolean values as int. THis will work
                    // for default tinyint comparing but not for json boolean comparing. https://github.com/doctrine/orm/issues/7550
                    if(isset($this->parameters[$parameter_name]) && is_bool($this->parameters[$parameter_name])) {
                        $leftSide = 'CAST('.$leftSide.' AS string)';
                        $this->parameters[$parameter_name] = $this->parameters[$parameter_name] ? 'true' : 'false';
                    }

                    return $this->getWhereStatement($filterInput['operator'], $leftSide, $rightSide);
                }
            }
        }

        return null;
    }

}
