<?php


namespace UniteCMS\CoreBundle\Content;

use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Validator\ContentValidatorInterface;

class ContentValidatorManager
{
    /**
     * @var ContentValidatorInterface[]
     */
    protected $contentValidators = [];

    /**
     * @param ContentValidatorInterface $validator
     * @return self
     */
    public function registerContentValidator(ContentValidatorInterface $validator) : self
    {
        if (!in_array($validator, $this->contentValidators)) {
            $this->contentValidators[] = $validator;
        }
        return $this;
    }

    /**
     * @return ContentValidatorInterface[]
     */
    public function getContentValidators() : array {
        return $this->contentValidators;
    }

    /**
     * @param string $domain
     * @return ContentValidatorInterface[]
     */
    public function getContentValidatorsForDomain(string $domain) {
        return array_filter($this->contentValidators, function(ContentValidatorInterface $validator) use($domain) {
            return $validator->supportsDomain($domain);
        });
    }
}
