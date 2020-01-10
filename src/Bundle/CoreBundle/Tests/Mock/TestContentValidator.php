<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\Validator\ContentValidatorInterface;

class TestContentValidator extends ConstraintValidator implements ContentValidatorInterface
{
    /**
     * @inheritDoc
     */
    function supportsDomain(string $domain): bool { return true; }

    /**
     * @inheritDoc
     */
    function supportsContentType(ContentType $contentType): bool { return true; }

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if($value->getFieldData('test_global_validator')) {
            $this->context
                ->buildViolation($value->getFieldData('test_global_validator')->getData())
                ->addViolation();
        }
    }
}
