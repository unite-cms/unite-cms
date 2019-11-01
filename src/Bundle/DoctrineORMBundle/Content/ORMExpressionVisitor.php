<?php


namespace UniteCMS\DoctrineORMBundle\Content;


use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use InvalidArgumentException;

class ORMExpressionVisitor extends ExpressionVisitor
{
    /**
     * @param string $field
     * @param string $alias
     *
     * @return string
     */
    protected function transformField(string $field, string $alias) : string {
        switch ($field) {
            case 'id':
            case 'type':
            case 'deleted':
            case 'username':
                return join('.', [$alias, $field]);

            case 'sensitive_data':
            case 'password_reset_token':
                throw new InvalidArgumentException();

            default: return $this->transformJSONField($field, $alias);
        }
    }

    /**
     * @param string $field
     * @param string $alias
     *
     * @return string
     */
    protected function transformJSONField(string $field, string $alias) : string {
        return sprintf("JSON_EXTRACT(%s.data, '$.%s')", $alias, $field);
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        return new Comparison(
            $this->transformField($comparison->getField(), 'c'),
            $comparison->getOperator(),
            $comparison->getValue());
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressions = [];

        foreach($expr->getExpressionList() as $expression) {
            $expressions[] = $expression->visit($this);
        }

        return new CompositeExpression($expr->getType(), $expressions);
    }
}
