<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.11.18
 * Time: 15:34
 */

namespace UniteCMS\CoreBundle\Service;

use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Exception\DomainAccessDeniedException;
use UniteCMS\CoreBundle\Exception\MissingContentTypeException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingDomainMemberTypeException;
use UniteCMS\CoreBundle\Exception\MissingFieldException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;

class ReferenceResolver
{
    /**
     * @var UniteCMSManager $uniteCMSManager
     */
    private $uniteCMSManager;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var Domain|null $fallbackDomain
     */
    private $fallbackDomain = null;

    /**
     * @var ContentType|null $fallbackContentType
     */
    private $fallbackContentType = null;

    public function __construct(UniteCMSManager $uniteCMSManager, EntityManager $entityManager, AuthorizationChecker $authorizationChecker)
    {
        $this->uniteCMSManager = $uniteCMSManager;
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function setFallbackFromContext(ExecutionContextInterface $context, $settings) {

        $this->fallbackDomain = null;
        $this->fallbackContentType = null;

        if(!$context->getRoot() instanceof Domain) {
            return;
        }

        // If we are referencing the current domain during creation
        if(empty($context->getRoot()->getId()) && $context->getRoot()->getIdentifier() === $settings->domain) {
            $this->fallbackDomain = $context->getRoot();
        }

        // If we are referencing the current domain during update
        if(!empty($this->uniteCMSManager->getDomain()) && $context->getRoot()->getId() === $this->uniteCMSManager->getDomain()->getId()) {

            if($context->getRoot()->getIdentifier() === $settings->domain || $context->getRoot()->getPreviousIdentifier() === $settings->domain) {
                $this->fallbackDomain = $context->getRoot();
            }

            $fallbackContentType = $context->getRoot()->getContentTypes()->filter(
                function (ContentType $contentType) use ($settings) {
                    return $contentType->getIdentifier() == $settings->content_type;
                }
            )->first();
            $this->fallbackContentType = $fallbackContentType ? $fallbackContentType : null;
        }
    }

    /**
     * Resolves a reference content type field and check permission for it.
     *
     * @param string $domain_identifier
     * same identifier as domain_identifier, we return this object.
     *
     * @return Domain
     * @throws DomainAccessDeniedException
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     */
    public function resolveDomain(string $domain_identifier): Domain
    {

        if (!$domain_identifier) {
            throw new InvalidArgumentException("You must pass a domain identifier.");
        }

        // Only allow to resolve a content type from the same organization.
        $organization = $this->uniteCMSManager->getOrganization();

        if (!$organization) {
            throw new MissingOrganizationException(
                "Organization Missing."
            );
        }

        // If a fallback domain was found, use this domain.
        if(!empty($this->fallbackDomain)) {
            if($this->fallbackDomain->getIdentifier() === $domain_identifier) {
                $domain = $this->fallbackDomain;
            }
        } else {
            $domain = $organization->getDomains()->filter(
                function (Domain $domain) use ($domain_identifier) {
                    return $domain->getIdentifier() == $domain_identifier;
                }
            )->first();

            // We need to reload the full domain. uniteCMSManager only holds infos for the current domain.
            if($domain) {
                $domain = $this->entityManager->getRepository('UniteCMSCoreBundle:Domain')->findOneBy(
                    [
                        'organization' => $organization,
                        'id' => $domain->getId(),
                    ]
                );
            }
        }

        if(empty($domain)) {
            throw new MissingDomainException(
                "A reference field was configured with domain \"{$domain_identifier}\". However \"{$domain_identifier}\" does not exist, or you don't have access to it."
            );
        }

        if (!$this->authorizationChecker->isGranted(DomainVoter::VIEW, $domain)) {
            throw new DomainAccessDeniedException(
                "A reference field was configured with domain \"{$domain_identifier}\". However you are not allowed to access it."
            );
        }

        return $domain;
    }

    /**
     * @param Domain $domain
     * @param $content_type_identifier
     * identifier as content_type_identifier, we return this object.
     * @return ContentType
     * @throws MissingContentTypeException
     */
    public function resolveContentType(Domain $domain, $content_type_identifier) : ContentType {

        if (!$content_type_identifier) {
            throw new InvalidArgumentException("You must pass a content type identifier.");
        }

        /**
         * @var ContentType $contentType
         */
        $contentType = $domain->getContentTypes()->filter(
            function (ContentType $contentType) use ($content_type_identifier) {
                return $contentType->getIdentifier() === $content_type_identifier;
            }
        )->first();

        if (!$contentType) {

            if(!empty($this->fallbackContentType) && $this->fallbackContentType->getDomain()->getId() === $domain->getId() && $this->fallbackContentType->getIdentifier() === $content_type_identifier) {
                $contentType = $this->fallbackContentType;
            } else {
                throw new MissingContentTypeException(
                    "A reference field was configured with content type \"{$content_type_identifier}\" on domain \"{$domain->getIdentifier()}\". However \"{$content_type_identifier}\" does not exist."
                );
            }
        }

        return $contentType;
    }

    /**
     * @param Domain $domain
     * @param $domain_member_type_identifier
     * identifier as content_type_identifier, we return this object.
     * @return DomainMemberType
     * @throws MissingDomainMemberTypeException
     */
    public function resolveDomainMemberType(Domain $domain, $domain_member_type_identifier) : DomainMemberType {
        if (!$domain_member_type_identifier) {
            throw new InvalidArgumentException("You must pass a domain member type identifier.");
        }

        /**
         * @var DomainMemberType $domainMemberType
         */
        $domainMemberType = $domain->getDomainMemberTypes()->filter(
            function (DomainMemberType $domainMemberType) use ($domain_member_type_identifier) {
                return $domainMemberType->getIdentifier() === $domain_member_type_identifier;
            }
        )->first();

        if (!$domainMemberType) {

            if(!empty($this->fallbackDomainMemberType) && $this->fallbackDomainMemberType->getDomain()->getId() === $domain->getId() && $this->fallbackDomainMemberType->getIdentifier() === $domainMemberType) {
                $domainMemberType = $this->fallbackDomainMemberType;
            } else {
                throw new MissingDomainMemberTypeException(
                    "A reference field was configured with domain member type \"{$domain_member_type_identifier}\" on domain \"{$domain->getIdentifier()}\". However \"{$domain_member_type_identifier}\" does not exist."
                );
            }
        }

        return $domainMemberType;
    }

    /**
     * @param Domain $domain
     * @param $settings
     * @return Fieldable
     * @throws MissingContentTypeException
     * @throws MissingDomainMemberTypeException
     */
    public function resolveFieldable(Domain $domain, $settings) : Fieldable {
        if(!empty($settings->content_type)) {
            return $this->resolveContentType($domain, $settings->content_type);
        } else {
            return $this->resolveDomainMemberType($domain, $settings->domain_member_type);
        }
    }

    /**
     * @param Fieldable $fieldable
     * @param string $field_identifier
     * @param string|null $field_type
     * @return FieldableField
     * @throws MissingFieldException
     */
    public function resolveField(Fieldable $fieldable, string $field_identifier, string $field_type = null) : FieldableField {

        if (!$field_identifier) {
            throw new InvalidArgumentException("You must pass a field identifier.");
        }

        /**
         * @var FieldableField $field
         */
        $field = $fieldable->getFields()->filter(
            function(ContentTypeField $field) use ($field_identifier, $field_type) {
                return $field->getIdentifier() === $field_identifier && (!$field_type || $field->getType() === $field_type);
            }
        )->first();

        if(!$field) {
            throw new MissingFieldException("A reference field was configured with reference field \"{$field_identifier}\" on type \"{$fieldable->getIdentifier()}\". However \"{$field_identifier}\" does not exist or is of wrong type.");
        }

        return $field;
    }
}