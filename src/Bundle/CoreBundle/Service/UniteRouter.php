<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.09.18
 * Time: 12:55
 */

namespace UniteCMS\CoreBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class UniteRouter extends Router
{
    /**
     * @param string $name
     * @param array|object $parameters
     * @return array
     */
    protected function findMissingParameters($name, $parameters) : array {
        // TODO
    }

    /**
     * Generates a url for given route and parameters.
     *
     * If a known entity is passed as parameter, tries to fill out other parameters by properties and referenced
     * entities of the entity. Sets the default reference type to ABSOLUTE_URL, because when using the subdomain
     * approach, absolute_url generation is required.
     *
     * @param $name
     * @param array|object $parameters, an array of string and/or entity parameters, or an entity object.
     * @param int $referenceType
     * @return string
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_URL)
    {
        $parameters = $this->findMissingParameters($name, $parameters);
        return parent::generate($name, $parameters, $referenceType);
    }
}