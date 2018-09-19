<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Exception\ContentAccessDeniedException;
use UniteCMS\CoreBundle\Exception\ContentTypeAccessDeniedException;
use UniteCMS\CoreBundle\Exception\DomainAccessDeniedException;
use UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException;
use UniteCMS\CoreBundle\Exception\MissingContentTypeException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Form\ReferenceType;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;
use UniteCMS\CoreBundle\View\ViewTypeInterface;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ReferenceFieldType extends FieldType
{
    const TYPE = "reference";
    const FORM_TYPE = ReferenceType::class;
    const SETTINGS = ['domain', 'content_type', 'view', 'content_label'];
    const REQUIRED_SETTINGS = ['domain', 'content_type'];

    private $validator;
    private $authorizationChecker;
    private $uniteCMSManager;
    private $viewTypeManager;
    private $entityManager;
    private $templating;
    private $csrfTokenManager;
    private $router;

    function __construct(
        ValidatorInterface $validator,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager,
        EntityManager $entityManager,
        ViewTypeManager $viewTypeManager,
        TwigEngine $templating,
        Router $router,
        CsrfTokenManager $csrfTokenManager
    ) {
        $this->validator = $validator;
        $this->authorizationChecker = $authorizationChecker;
        $this->uniteCMSManager = $uniteCMSManager;
        $this->viewTypeManager = $viewTypeManager;
        $this->entityManager = $entityManager;
        $this->templating = $templating;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Resolves an content type and checks permission for the domain.
     *
     * @param string $domain_identifier
     * @param string $content_type_identifier
     * @return ContentType
     * @throws DomainAccessDeniedException
     * @throws MissingContentTypeException
     * @throws MissingDomainException
     * @throws MissingOrganizationException
     */
    private function resolveContentType($domain_identifier, $content_type_identifier): ContentType
    {

        if (!$domain_identifier || !$content_type_identifier) {
            throw new InvalidArgumentException("You must pass a domain and content_type identifier.");
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
            throw new MissingDomainException(
                "A reference field was configured to reference to domain \"{$domain_identifier}\". However \"{$domain_identifier}\" does not exist, or you don't have access to it."
            );
        }

        // We need to reload the full domain. uniteCMSManager only holds infos for the current domain.
        $domain = $this->entityManager->getRepository('UniteCMSCoreBundle:Domain')->findOneBy(
            [
                'organization' => $organization,
                'id' => $domain->getId(),
            ]
        );

        if (!$this->authorizationChecker->isGranted(DomainVoter::VIEW, $domain)) {
            throw new DomainAccessDeniedException(
                "A reference field was configured to reference to domain \"{$domain_identifier}\". However you are not allowed to access it."
            );
        }

        $contentType = $domain->getContentTypes()->filter(
            function (ContentType $contentType) use ($content_type_identifier) {
                return $contentType->getIdentifier() == $content_type_identifier;
            }
        )->first();

        if (!$contentType) {
            throw new MissingContentTypeException(
                "A reference field was configured to reference to content type \"{$content_type_identifier}\" on domain \"{$domain_identifier}\". However \"{$content_type_identifier}\" does not exist."
            );
        }

        return $contentType;
    }

    /**
     * {@inheritdoc}
     * @throws ContentTypeAccessDeniedException
     * @throws InvalidFieldConfigurationException
     * @throws \Twig\Error\Error
     * @throws DomainAccessDeniedException
     * @throws MissingOrganizationException
     */
    function getFormOptions(FieldableField $field): array
    {
        $settings = $field->getSettings();
        $settings->view = $settings->view ?? 'all';

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($settings->domain, $settings->content_type);
        if (!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to view the content type \"{$settings->content_type}\".");
        }

        // Get view.
        $view = $contentType->getViews()->filter(
            function (View $view) use ($settings) {
                return $view->getIdentifier() == $settings->view;
            }
        )->first();
        if (!$view) {
            throw new InvalidFieldConfigurationException(
                "No view with identifier '{$settings->view}' was found for this organization, domain and content type."
            );
        }

        // Reload the full view object.
        $view = $this->entityManager->getRepository('UniteCMSCoreBundle:View')->findOneBy(
            [
                'contentType' => $contentType,
                'id' => $view->getId(),
            ]
        );

        // Pass the rendered view HTML and other parameters as a form option.
        return array_merge(
            parent::getFormOptions($field),
            [
                'empty_data' => [
                    'domain' => $contentType->getDomain()->getIdentifier(),
                    'content_type' => $contentType->getIdentifier(),
                ],
                'attr' => [
                    'api-url' => $this->router->generate('unitecms_core_api', [$contentType]),
                    'content-label' => $settings->content_label ?? (empty(
                        $contentType->getContentLabel()
                        ) ? (string)$contentType.' #{id}' : $contentType->getContentLabel()),
                    'modal-html' => $this->templating->render(
                        $this->viewTypeManager->getViewType($view->getType())::getTemplate(),
                        [
                            'view' => $view,
                            'parameters' => $this->viewTypeManager
                                ->getTemplateRenderParameters($view, ViewTypeInterface::SELECT_MODE_SINGLE)
                                ->setCsrfToken($this->csrfTokenManager->getToken('fieldable_form')),
                        ]
                    ),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     * @throws InvalidFieldConfigurationException
     * @throws ContentTypeAccessDeniedException
     * @throws DomainAccessDeniedException
     * @throws MissingOrganizationException
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($field->getSettings()->domain, $field->getSettings()->content_type);

        if (!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to list content of content type \"{$contentType->getIdentifier()}\" on domain \"{$contentType->getDomain()->getIdentifier()}\".");
        }

        $name = IdentifierNormalizer::graphQLType($contentType);

        if ($nestingLevel > 0) {
            $name .= 'Level'.$nestingLevel;
        }

        // We use the default content factory to build the type.
        return $schemaTypeManager->getSchemaType($name, $contentType->getDomain(), $nestingLevel);
    }

    /**
     * {@inheritdoc}
     * @throws InvalidFieldConfigurationException
     * @throws ContentTypeAccessDeniedException
     * @throws DomainAccessDeniedException
     * @throws MissingOrganizationException
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {

        // Get content type and check if we have access to it.
        $contentType = $this->resolveContentType($field->getSettings()->domain, $field->getSettings()->content_type);
        if (!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to view the content type \"{$contentType}\".");
        }

        return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $contentType->getDomain(), $nestingLevel);
    }

    /**
     * Resolve reference data. This means getting the referenced entity, checking access and returning it.
     *
     * @param FieldableField $field
     * @param array $value
     * @return null|Content
     *
     * @throws InvalidFieldConfigurationException
     * @throws ContentAccessDeniedException
     * @throws DomainAccessDeniedException
     * @throws MissingOrganizationException
     */
    function resolveGraphQLData(FieldableField $field, $value)
    {
        if (empty($value)) {
            return null;
        }

        $contentType = $this->resolveContentType($value['domain'], $value['content_type']);

        // Find content for this content type.
        $content = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
            ['contentType' => $contentType, 'id' => $value['content']]
        );
        if (!$content) {
            throw new InvalidArgumentException("No content with id '{$value['content']}' was found.");
        }

        // Check access to view content.
        if (!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $content)) {
            throw new ContentAccessDeniedException("You are not allowed to view this content.");
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     *
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context)
    {

        // When deleting content, we don't need to validate data.
        if (strtoupper($context->getGroup()) === 'DELETE') {
            return;
        }

        // Only validate available data.
        if (empty($data)) {
            return;
        }

        // Make sure, that all required fields are set.
        if (empty($data['domain']) || empty($data['content_type']) || empty($data['content'])) {
            $context->buildViolation('missing_reference_definition')->atPath('['.$field->getIdentifier().']')->addViolation();
        } // Try to resolve the data to check if the current user is allowed to access it.
        else {
            try {
                $this->resolveGraphQLData($field, $data);
            } catch (\Exception $e) {
                $context->buildViolation('invalid_reference_definition')->atPath('['.$field->getIdentifier().']')->addViolation();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if ($context->getViolations()->count() > 0) {
            return;
        }

        // Try to resolve content type. If it don't throw an exception, the domain and content_type exist and the user can access it.
        try {
            $this->resolveContentType($settings->domain, $settings->content_type);
        }
        catch (DomainAccessDeniedException $e) {
            $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();
        } catch (MissingOrganizationException $e) {
            $context->buildViolation('invalid_organization')->atPath('domain')->addViolation();
        }


        // Special case 1: We are validating a new domain, that is not already persisted.
        catch (MissingDomainException $e) {

            if($context->getRoot() instanceof Domain && empty($context->getRoot()->getId())) {

                // If we don't reference the new domain, but the domain also does not exist, we can't reference it.
                if($context->getRoot()->getIdentifier() != $settings->domain) {
                    $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();
                    return;
                }

                $contentType = $context->getRoot()->getContentTypes()->filter(
                    function (ContentType $contentType) use ($settings) {
                        return $contentType->getIdentifier() == $settings->content_type;
                    }
                )->first();

                // If we referenced content_type was not found in our new domain, we can't reference it.
                if(!$contentType) {
                    $context->buildViolation('invalid_content_type')->atPath('content_type')->addViolation();
                    return;
                }

                return;
            }

            $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();



        // Special case 2: Domain does exist, but we are updating the domain at the moment, adding a new content_type.
        } catch (MissingContentTypeException $e) {

            if($context->getRoot() instanceof Domain && !empty($this->uniteCMSManager->getDomain())  && $context->getRoot()->getId() === $this->uniteCMSManager->getDomain()->getId()) {
                if(!$context->getRoot()->getContentTypes()->filter(
                    function (ContentType $contentType) use ($settings) {
                        return $contentType->getIdentifier() == $settings->content_type;
                    }
                )->first()) {
                    $context->buildViolation('invalid_content_type')->atPath('content_type')->addViolation();
                    return;
                }

                return;
            }

            $context->buildViolation('invalid_content_type')->atPath('content_type')->addViolation();
        }
    }
}
