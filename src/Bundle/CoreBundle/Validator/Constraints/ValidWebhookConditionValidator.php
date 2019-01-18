<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.08.18
 * Time: 12:06
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidWebhookConditionValidator extends ConstraintValidator
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
        if(empty($value)) {
            return;
        }

        /**
         * @var UniteExpressionChecker $expressionChecker
         */
        $expressionChecker = new UniteExpressionChecker();
        $expressionChecker->registerVariable('event');

        /**
         * @var FieldableContent $content
         */
        $content = $this->context->getObject();

        if($content instanceof FieldableContent) {
            $expressionChecker->registerFieldableContent($content);
        }

        if($content instanceof Content) {
            $expressionChecker->registerDoctrineContentFunctionsProvider($this->entityManager, $content->getContentType());
        }

        if (!$expressionChecker->validate($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}