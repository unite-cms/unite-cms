<?php

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\Content;

class ValidContentTranslationOfValidator extends ConstraintValidator
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if($value == null) {
            return;
        }

        if($this->context->getObject() == null) {
            return;
        }

        if (!$this->context->getObject() instanceof Content) {
            throw new InvalidArgumentException(
                'The ValidContentTranslationOfValidator constraint expects a UniteCMS\CoreBundle\Entity\Content object.'
            );
        }

        if (!$value instanceof Content) {
            throw new InvalidArgumentException(
                'The ValidContentTranslationOfValidator constraint expects a UniteCMS\CoreBundle\Entity\Content value.'
            );
        }

        /**
         * @var Content $content
         */
        $content = $this->context->getObject();

        // We also want to check uniqueness, even if translationOf was soft deleted.
        $this->entityManager->getFilters()->disable('gedmo_softdeleteable');

        if($value->getLocale() === $content->getLocale()) {
            $this->context->buildViolation($constraint->uniqueLocaleMessage)
                ->setInvalidValue(null)
                ->atPath('[translationOf]')
                ->addViolation();
        }

        $this->entityManager->getFilters()->enable('gedmo_softdeleteable');
    }
}
