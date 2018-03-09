<?php

namespace UnitedCMS\CoreBundle\Service;

use JMS\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Domain;

class DomainDefinitionParser
{
    /**
     * @var Serializer $serializer
     */
    private $serializer;

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    public function __construct(Serializer $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Parses the given domain definition in JSON format and returns a new domain object.
     *
     * @param string $JSON
     * @throws \InvalidArgumentException
     * @return Domain
     */
    public function parse(string $JSON): Domain
    {
        return $this->serializer->deserialize($JSON, Domain::class, 'json');
    }

    /**
     * Returns a JSON string for the given Domain
     *
     * @param Domain $domain
     * @throws \InvalidArgumentException
     * @return string
     */
    public function serialize(Domain $domain): string
    {
        return $this->serializer->serialize($domain, 'json');
    }
}