<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidValidationsValidator extends ConstraintValidator
{
    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if(!is_array($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
            return;
        }

        foreach($value as $index => $validation) {

            if(!$validation instanceof FieldableValidation) {
                $this->context->buildViolation($constraint->message)->addViolation();
                return;
            }

            if(empty($validation->getExpression())) {
                $this->context->buildViolation($constraint->message)->atPath("[$index]")->addViolation();
                return;
            }

            if(!is_string($validation->getExpression()) || !is_string($validation->getMessage()) || !is_string($validation->getPath())) {
                $this->context->buildViolation($constraint->message)->atPath("[$index]")->addViolation();
                return;
            }

            $expressionChecker = new UniteExpressionChecker();
            $expressionChecker
                ->registerDoctrineContentFunctionsProvider($this->entityManager, new ContentType())
                ->registerFieldableContent(null);

            if(!$expressionChecker->validate($validation->getExpression())) {
                $this->context->buildViolation($constraint->message)->atPath("[$index][expression]")->addViolation();
                return;
            }

            foreach($validation->getGroups() as $group) {
                if(!in_array($group, ['CREATE', 'UPDATE', 'DELETE'])) {
                    $this->context->buildViolation($constraint->message)->atPath("[$index][group]")->addViolation();
                    return;
                }
            }
        }
    }
}
