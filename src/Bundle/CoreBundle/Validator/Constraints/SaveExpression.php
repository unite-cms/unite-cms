<?php


namespace UniteCMS\CoreBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraints\Expression;

class SaveExpression extends Expression {

    /**
     * @inheritDoc
     */
    public function validatedBy()
    {
        return static::class.'Validator';
    }

}
