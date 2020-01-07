<?php

namespace UniteCMS\CoreBundle\Query;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Exception\UnknownFieldException;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class ContentCriteriaBuilder
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * @param ContentType $contentType
     * @param array $where
     *
     * @return Expression
     * @throws UnknownFieldException
     */
    protected function buildNestedWhereExpression(ContentType $contentType, array $where) : Expression {

        if(!empty($where['AND'])) {
            return new CompositeExpression(
                CompositeExpression::TYPE_AND,
                array_map(function($and_where) use($contentType) {
                    return $this->buildNestedWhereExpression($contentType, $and_where);
                }, array_filter($where['AND']))
            );
        }

        else if(!empty($where['OR'])) {
            return new CompositeExpression(
                CompositeExpression::TYPE_OR,
                array_map(function($or_where) use($contentType) {
                    return $this->buildNestedWhereExpression($contentType, $or_where);
                }, array_filter($where['OR']))
            );
        }

        elseif(!empty($where['field']) && !empty($where['operator'])) {

            if(in_array($where['field'], ContentCriteria::BASE_FIELDS)) {
                return new BaseFieldComparison(
                    $where['field'],
                    ContentCriteria::OPERATOR_MAP[$where['operator']],
                    ContentCriteria::castValue($where['value'], $where['cast'] ?? null)
                );
            }

            else if($field = $contentType->getField($where['field'])) {
                $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
                if($comparison = $fieldType->queryComparison($field, $where)) {
                    return $comparison;
                }
            }
        }

        throw new UnknownFieldException(sprintf('Field "%s" was not found on type "%s".', $where['field'], $contentType->getId()));
    }

    /**
     * @param array $args
     * @param ContentType $contentType
     *
     * @return ContentCriteria
     * @throws UnknownFieldException
     */
    public function build(array $args, ContentType $contentType) : ContentCriteria {

        $criteria = new ContentCriteria();
        $criteria
            ->setFirstResult($args['offset'] ?? 0)
            ->setMaxResults($args['limit'] ?? 20);

        if(!empty($args['orderBy'])) {
            $orderBy = [];

            foreach($args['orderBy'] as $ordering) {
                if(in_array($ordering['field'], ContentCriteria::BASE_FIELDS)) {
                    $orderBy[] = new BaseFieldOrderBy($ordering['field'], $ordering['order']);
                }

                else if($field = $contentType->getField($ordering['field'])) {
                    $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
                    if($sortBy = $fieldType->queryOrderBy($field, $ordering)) {
                        $orderBy[] = $sortBy;
                    }
                }
            }
            $criteria->orderBy($orderBy);
        }

        if(!empty($args['filter'])) {
            $criteria->where(
                $this->buildNestedWhereExpression($contentType, $args['filter'])
            );
        }

        return $criteria;

    }
}
