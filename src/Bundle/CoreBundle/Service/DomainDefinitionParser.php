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
     * Variables can be used inside each other. This function resolves an array of variables until all variables
     * contain no more other variables.
     *
     * @param array $variables , keys are variable names (and must start with @), values are JSON strings.
     * @param int $level, We allow to replace only variables that are nested 8 times.
     *
     * @return array
     */
    private function prepareVariables(array $variables = [], int $level = 0) : array {

        if($level > 7) {
            return $variables;
        }

        $sill_contains_variable = false;

        foreach($variables as $key => $value) {
            foreach($variables as $_key => $_value) {
                $variables[$key] = str_replace('"' . $_key . '"', $_value, $variables[$key]);

                // If this replacement changed the value of the variable, we need another round to check nested replacements.
                if($variables[$key] != $value) {
                    $sill_contains_variable = true;
                }
            }
        }

        if($sill_contains_variable) {
            $variables = $this->prepareVariables($variables, $level + 1);
        }

        return $variables;
    }

    /**
     * Parses the given domain definition in JSON format and returns a new domain object.
     *
     * @param string $JSON
     *
     * @return Domain
     */
    public function parse(string $JSON): Domain
    {
        // First check if there are any variables in the json config, prepare them and remove them from the JSON doc.
        $json_doc = json_decode($JSON, true);
        $variables = [];

        if(isset($json_doc['variables'])) {

            $variables = array_map(function($value){ return json_encode($value); }, $json_doc['variables']);
            $variables = $this->prepareVariables($variables);
            unset($json_doc['variables']);
            $JSON = json_encode($json_doc);
        }

        // Then replace all occurrences in the JSON doc with the variable values.
        foreach($variables as $variable => $value) {
            $JSON = str_replace('"' . $variable . '"', $value, $JSON);
        }

        // Finally use the serializer to deserialize the json config into an domain entity.

        /**
         * @var Domain $domain
         */
        $domain = $this->serializer->deserialize($JSON, Domain::class, 'json');
        return $domain;
    }

    /**
     * Returns a JSON string for the given Domain.
     *
     * @param Domain $domain
     * @return string
     */
    public function serialize(Domain $domain): string
    {
        return $this->serializer->serialize($domain, 'json');
    }
}
