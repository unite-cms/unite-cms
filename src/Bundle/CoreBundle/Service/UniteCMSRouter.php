<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.09.18
 * Time: 12:55
 */

namespace UniteCMS\CoreBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;

/**
 * Extends the default Symfony router to allow to pass unite entities instead of parameters.
 *
 * You can pass an entity instead of the parameters array, or you can pass an parameters array that includes entities
 * and string parameters.
 *
 */
class UniteCMSRouter extends Router
{
    /**
     * Generates a url for the given route and given parameters.
     *
     * If a known entity is passed as parameter, tries to fill out other parameters by properties and referenced
     * entities of the entity. Sets the reference type to ABSOLUTE_URL, because when using the subdomain
     * approach, absolute_url generation is required.
     *
     * @param $name
     * @param array|object $parameters, an array of string and/or entity parameters, or an entity object.
     * @param int $referenceType
     * @return string
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_URL)
    {
        // In unite cms we can only handle absolute urls, because of the subdomain routing approach.
        $referenceType = self::ABSOLUTE_URL;

        $this->context->setParameters(array_merge($this->context->getParameters(), $this->findAdditionalParameters($parameters)));
        return parent::generate($name, $parameters, $referenceType);
    }

    /**
     * Finds additional parameters by searching for entities and use them to identifier parameters. All found entities
     * will be removed from parameters.
     * @param array|object $parameters
     * @return array
     */
    protected function findAdditionalParameters(&$parameters) : array {

        $resolved_parameters = [];

        if(is_object($parameters)) {
            $parameters = [$parameters];
        }

        // If a parameter is an object, use it to find parameters.
        foreach($parameters as $key => $value) {
            if(is_object($value)) {
                $this->replaceParametersForEntity($value, $resolved_parameters);
                unset($parameters[$key]);
            }
        }

        return $resolved_parameters;
    }

    /**
     * (Recursively) maps entities to parameters.
     *
     * @param $entity
     * @param array $parameters
     */
    protected function replaceParametersForEntity($entity, array &$parameters = []) : void {

        if(!is_object($entity)) {
            return;
        }

        if($entity instanceof Organization) {
            $parameters['organization'] = IdentifierNormalizer::denormalize($entity->getIdentifier());
        }

        if($entity instanceof Domain) {
            $this->replaceParametersForEntity($entity->getOrganization(), $parameters);
            $parameters['domain'] = IdentifierNormalizer::denormalize($entity->getIdentifier());
        }

        if($entity instanceof ContentType) {
            $this->replaceParametersForEntity($entity->getDomain(), $parameters);
            $parameters['content_type'] = IdentifierNormalizer::denormalize($entity->getIdentifier());
            $parameters['view'] = 'all';
        }

        if($entity instanceof View) {
            $this->replaceParametersForEntity($entity->getContentType(), $parameters);
            $parameters['view'] = IdentifierNormalizer::denormalize($entity->getIdentifier());
        }

        if($entity instanceof SettingType) {
            $this->replaceParametersForEntity($entity->getDomain(), $parameters);
            $parameters['setting_type'] = IdentifierNormalizer::denormalize($entity->getIdentifier());
        }

        if($entity instanceof DomainMemberType) {
            $this->replaceParametersForEntity($entity->getDomain(), $parameters);
            $parameters['member_type'] = IdentifierNormalizer::denormalize($entity->getIdentifier());
        }

        if($entity instanceof Content) {
            $this->replaceParametersForEntity($entity->getContentType(), $parameters);
            $parameters['content'] = IdentifierNormalizer::denormalize($entity->getId());
        }

        if($entity instanceof Setting) {
            $this->replaceParametersForEntity($entity->getSettingType(), $parameters);
            $parameters['setting'] = IdentifierNormalizer::denormalize($entity->getId());
        }

        if($entity instanceof DomainMember) {
            $this->replaceParametersForEntity($entity->getDomainMemberType(), $parameters);
            $parameters['member'] = IdentifierNormalizer::denormalize($entity->getId());
        }

        if($entity instanceof OrganizationMember) {
            $this->replaceParametersForEntity($entity->getOrganization(), $parameters);
            $parameters['member'] = IdentifierNormalizer::denormalize($entity->getId());
        }

        if($entity instanceof Invitation) {
            $this->replaceParametersForEntity($entity->getDomainMemberType(), $parameters);
            $parameters['invite'] = IdentifierNormalizer::denormalize($entity->getId());
        }

        if($entity instanceof ApiKey) {
            $this->replaceParametersForEntity($entity->getOrganization(), $parameters);
            $parameters['apiKey'] = IdentifierNormalizer::denormalize($entity->getId());
        }
    }
}