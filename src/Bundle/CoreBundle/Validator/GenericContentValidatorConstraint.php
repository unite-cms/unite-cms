<?php


namespace UniteCMS\CoreBundle\Validator;

use Symfony\Component\Validator\Constraint;
use UniteCMS\CoreBundle\ContentType\ContentType;

/**
 * Create constraints based on ContentValidator implementations
 */
class GenericContentValidatorConstraint extends Constraint
{
    /**
     * @var ContentValidatorInterface $contentValidator
     */
    protected $contentValidator;

    public function __construct(ContentValidatorInterface $contentValidator)
    {
        $this->contentValidator = $contentValidator;
        parent::__construct($contentValidator->options());
    }

    /**
     * @param ContentType $contentType
     * @return bool
     */
    public function supportsContentType(ContentType $contentType) : bool {
        return $this->contentValidator->supportsContentType($contentType);
    }

    /**
     * @inheritDoc
     */
    public function validatedBy()
    {
        return get_class($this->contentValidator);
    }
}
