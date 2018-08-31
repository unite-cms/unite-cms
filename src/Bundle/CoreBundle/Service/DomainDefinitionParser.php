<?php

namespace UniteCMS\CoreBundle\Service;

use JMS\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Domain;

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
     * @param string $variablesJSON
     *   If a variables array is passed, all occurrences will be replaced before parsing.
     *
     * @return Domain
     */
    public function parse(string $JSON, string $variablesJSON = null): Domain
    {
        if(!empty($variablesJSON)) {
            $variables = json_decode($variablesJSON);
            foreach(get_object_vars($variables) as $variable => $value) {
                $value = json_encode($value);
                $JSON = str_replace('"'.$variable.'"', $value, $JSON);
            }
        }

        /**
         * @var Domain $domain
         */
        $domain = $this->serializer->deserialize($JSON, Domain::class, 'json');

        // Save variables to the domain.
        if($variablesJSON) {
            $domain->setConfigVariables(json_decode($variablesJSON, true));
        }

        return $domain;
    }

    /**
     * Returns a JSON string for the given Domain.
     *
     * If the domain contains variables, all occurrences will be replaced before returning the JSON.
     *
     * @param Domain $domain
     *
     * @return string
     */
    public function serialize(Domain $domain): string
    {
        $JSON = $this->serializer->serialize($domain, 'json');

        if(!empty($domain->getConfigVariables())) {
            foreach($domain->getConfigVariables() as $variable => $value) {
                $value = json_encode($value);
                $JSON = str_replace($value, '"'.$variable.'"', $JSON);
            }
        }

        return $JSON;
    }
}
