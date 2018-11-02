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
use UniteCMS\CoreBundle\Exception\DomainAccessDeniedException;
use UniteCMS\CoreBundle\Exception\MissingContentTypeException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
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
        if($context->getRoot() instanceof Domain && empty($context->getRoot()->getId()) && $context->getRoot()->getIdentifier() === $settings->domain) {
            $this->fallbackDomain = $context->getRoot();
        }

        if(!empty($this->uniteCMSManager->getDomain()) &&  $context->getRoot() instanceof Domain && $context->getRoot()->getId() === $this->uniteCMSManager->getDomain()->getId()) {
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

        $domain = $organization->getDomains()->filter(
            function (Domain $domain) use ($domain_identifier) {
                return $domain->getIdentifier() == $domain_identifier;
            }
        )->first();

        if (!$domain) {
            if(!empty($this->fallbackDomain) && $this->fallbackDomain->getIdentifier() === $domain_identifier) {
                $domain = $this->fallbackDomain;
            } else {
                throw new MissingDomainException(
                    "A reference field was configured with domain \"{$domain_identifier}\". However \"{$domain_identifier}\" does not exist, or you don't have access to it."
                );
            }

            // We need to reload the full domain. uniteCMSManager only holds infos for the current domain.
        } else {
            $domain = $this->entityManager->getRepository('UniteCMSCoreBundle:Domain')->findOneBy(
                [
                    'organization' => $organization,
                    'id' => $domain->getId(),
                ]
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
     * @param ContentType $contentType
     * @param string $field_identifier
     * @param string|null $field_type
     * @return ContentTypeField
     * @throws MissingFieldException
     */
    public function resolveField(ContentType $contentType, string $field_identifier, string $field_type = null) : ContentTypeField {

        if (!$field_identifier) {
            throw new InvalidArgumentException("You must pass a field identifier.");
        }

        /**
         * @var ContentTypeField $field
         */
        $field = $contentType->getFields()->filter(
            function(ContentTypeField $field) use ($field_identifier, $field_type) {
                return $field->getIdentifier() === $field_identifier && (!$field_type || $field->getType() === $field_type);
            }
        )->first();

        if(!$field) {
            throw new MissingFieldException("A reference field was configured with reference field \"{$field_identifier}\" on content type \"{$contentType->getIdentifier()}\". However \"{$field_identifier}\" does not exist or isn't a reference field.");
        }

        if($field->getSettings()->domain !== $contentType->getDomain()->getIdentifier() || $field->getSettings()->content_type !== $contentType->getIdentifier()) {
            throw new MissingFieldException("A reference field was configured with reference field \"{$field_identifier}\". However dies field holds a reference to a different content type.");
        }

        return $contentType->getFields()->get($field_identifier);
    }
}