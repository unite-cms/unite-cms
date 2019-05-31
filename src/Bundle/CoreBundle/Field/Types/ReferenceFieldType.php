<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Routing\Router;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Exception\ContentAccessDeniedException;
use UniteCMS\CoreBundle\Exception\ContentTypeAccessDeniedException;
use UniteCMS\CoreBundle\Exception\DomainAccessDeniedException;
use UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException;
use UniteCMS\CoreBundle\Exception\MissingContentTypeException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingDomainMemberTypeException;
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
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\ReferenceType;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Service\ReferenceResolver;
use UniteCMS\CoreBundle\View\Types\Factories\ViewConfigurationFactoryInterface;
use UniteCMS\CoreBundle\View\Types\TableViewType;
use UniteCMS\CoreBundle\View\ViewTypeInterface;
use UniteCMS\CoreBundle\View\ViewTypeManager;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ReferenceFieldType extends FieldType
{
    const TYPE = "reference";
    const FORM_TYPE = ReferenceType::class;
    const SETTINGS = ['not_empty', 'description', 'domain', 'content_type', 'domain_member_type', 'view', 'content_label', 'form_group'];
    const REQUIRED_SETTINGS = ['domain'];

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var ReferenceResolver $referenceResolver
     */
    private $referenceResolver;

    private $authorizationChecker;
    private $viewTypeManager;
    private $entityManager;
    private $templating;
    private $csrfTokenManager;
    private $router;
    private $tableViewConfigurationFactory;

    function __construct(
        ValidatorInterface $validator,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager,
        EntityManager $entityManager,
        ViewTypeManager $viewTypeManager,
        TwigEngine $templating,
        Router $router,
        CsrfTokenManager $csrfTokenManager,
        ViewConfigurationFactoryInterface $tableViewConfigurationFactory
    ) {
        $this->referenceResolver = new ReferenceResolver($uniteCMSManager, $entityManager, $authorizationChecker);
        $this->validator = $validator;
        $this->authorizationChecker = $authorizationChecker;
        $this->viewTypeManager = $viewTypeManager;
        $this->entityManager = $entityManager;
        $this->templating = $templating;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->tableViewConfigurationFactory = $tableViewConfigurationFactory;
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
        $contentLabel = $settings->content_label;
        $view = null;
        $viewFieldAssets = [];
        $viewParameters = [];

        /**
         * @var Fieldable $fieldable
         */
        $fieldable = $this->referenceResolver->resolveFieldable(
            $this->referenceResolver->resolveDomain($settings->domain),
            $settings
        );

        if ($fieldable instanceof ContentType && !$this->authorizationChecker->isGranted(ContentVoter::LIST, $fieldable)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to view the content type \"{$settings->content_type}\".");
        }

        if ($fieldable instanceof DomainMemberType && !$this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $fieldable)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to view the domain member type \"{$settings->domain_member_type}\".");
        }

        if ($fieldable instanceof DomainMemberType) {
            $viewParameters = $this->viewTypeManager
                ->getTemplateRenderParametersForDomainMemberType($fieldable, ViewTypeInterface::SELECT_MODE_SINGLE, [
                    'fields' => [
                        'id' => [
                            'label' => 'ID',
                            'type' => 'id',
                        ],
                        '_name' => [
                            'label' => 'Name',
                            'type' => 'text',
                        ],
                        'created' => [
                            'label' => 'Created',
                            'type' => 'date',
                        ],
                        'updated' => [
                            'label' => 'Updated',
                            'type' => 'date',
                        ],
                    ],
                    'sort' => [
                        'field' => 'updated',
                        'asc' => false,
                    ],
                    'contentType' => $fieldable->getIdentifier() . 'Member',
                    'hasTranslations' => false,
                ])
                ->setCsrfToken($this->csrfTokenManager->getToken('fieldable_form'));

            if(empty($contentLabel)) {
                $contentLabel = $fieldable->getDomainMemberLabel() ? $fieldable->getDomainMemberLabel() : (string)$fieldable.' #{id}';
            }
        }

        if ($fieldable instanceof ContentType) {
            // Get view.
            $settings->view = $settings->view ?? 'all';
            $view = $fieldable->getViews()->filter(
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
                    'contentType' => $fieldable,
                    'id' => $view->getId(),
                ]
            );

            $viewParameters = $this->viewTypeManager
                ->getTemplateRenderParameters($view, ViewTypeInterface::SELECT_MODE_SINGLE)
                ->setCsrfToken($this->csrfTokenManager->getToken('fieldable_form'));

            // Add all view field assets to the form, so they get included only once and at form rendering time.
            $viewFieldSettings = $viewParameters->getSettings();
            if(!empty($viewFieldSettings['fields'])) {
                foreach ($viewFieldSettings['fields'] as $viewFieldKey => $viewField) {
                    if(!empty($viewField['assets'])) {
                        $viewFieldAssets = array_merge($viewFieldAssets, $viewField['assets']);
                        $viewFieldSettings['fields'][$viewFieldKey]['assets'] = [];
                    }
                }
            }
            $viewParameters->setSettings($viewFieldSettings);

            if(empty($contentLabel)) {
                $contentLabel = $fieldable->getContentLabel() ? $fieldable->getContentLabel() : (string)$fieldable.' #{id}';
            }
        }

        // Pass the rendered view HTML and other parameters as a form option.
        return array_merge(
            parent::getFormOptions($field),
            [
                'empty_data' => [
                    'domain' => $fieldable->getDomain()->getIdentifier(),
                    'content_type' => $fieldable->getIdentifier(),
                ],
                'assets' => $viewFieldAssets,
                'attr' => [
                    'api-url' => $this->router->generate('unitecms_core_api', [$fieldable]),
                    'content-label' => $contentLabel,
                    'fieldable-type' => ($fieldable instanceof ContentType ? 'content' : 'member'),
                    'modal-html' => $this->templating->render(
                        ($view ?
                            $this->viewTypeManager->getViewType($view->getType())::getTemplate() :
                            TableViewType::getTemplate()
                        ),
                        [
                            'view' => $view,
                            'parameters' => $viewParameters,
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
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        /**
         * @var Fieldable $fieldable
         */
        $fieldable = $this->referenceResolver->resolveFieldable(
            $this->referenceResolver->resolveDomain($field->getSettings()->domain),
            $field->getSettings()
        );

        if ($fieldable instanceof ContentType && !$this->authorizationChecker->isGranted(ContentVoter::LIST, $fieldable)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to list content of content type \"{$fieldable->getIdentifier()}\" on domain \"{$fieldable->getDomain()->getIdentifier()}\".");
        }

        if ($fieldable instanceof DomainMemberType && !$this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $fieldable)) {
            throw new ContentTypeAccessDeniedException("You are not allowed to list members of domain member type \"{$fieldable->getIdentifier()}\" on domain \"{$fieldable->getDomain()->getIdentifier()}\".");
        }

        $name = IdentifierNormalizer::graphQLType($fieldable);

        // We use the default content factory to build the type.
        return $schemaTypeManager->getSchemaType($name, $fieldable->getDomain());
    }

    /**
     * {@inheritdoc}
     * @throws InvalidFieldConfigurationException
     * @throws DomainAccessDeniedException
     * @throws MissingOrganizationException
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        /**
         * @var Fieldable $fieldable
         */
        $fieldable = $this->referenceResolver->resolveFieldable(
            $this->referenceResolver->resolveDomain($field->getSettings()->domain),
            $field->getSettings()
        );

        if ($fieldable instanceof ContentType && !$this->authorizationChecker->isGranted(ContentVoter::LIST, $fieldable)) {
            return null;
        }

        if ($fieldable instanceof DomainMemberType && !$this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $fieldable)) {
            return null;
        }

        return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $fieldable->getDomain());
    }

    /**
     * Resolve reference data. This means getting the referenced entity, checking access and returning it.
     *
     * @param FieldableField $field
     * @param array $value
     * @param FieldableContent $content
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     * @return null|Content
     *
     * @throws ContentAccessDeniedException
     * @throws DomainAccessDeniedException
     * @throws MissingContentTypeException
     * @throws MissingDomainException
     * @throws MissingDomainMemberTypeException
     * @throws MissingOrganizationException
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        if (empty($value)) {
            return null;
        }

        /**
         * @var Fieldable $fieldable
         */
        $fieldable = $this->referenceResolver->resolveFieldable(
            $this->referenceResolver->resolveDomain($field->getSettings()->domain),
            $field->getSettings()
        );

        /**
         * @var FieldableContent $fieldableContent
         */
        $fieldableContent = null;

        if ($fieldable instanceof ContentType && $this->authorizationChecker->isGranted(ContentVoter::LIST, $fieldable)) {
            $fieldableContent = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->findOneBy(
                ['contentType' => $fieldable, 'id' => $value['content']]
            );
        }

        if ($fieldable instanceof DomainMemberType && $this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $fieldable)) {
            $fieldableContent = $this->entityManager->getRepository('UniteCMSCoreBundle:DomainMember')->findOneBy(
                ['domainMemberType' => $fieldable, 'id' => $value['content']]
            );
        }

        if (!$fieldableContent) {
            throw new InvalidArgumentException("No content / member with id '{$value['content']}' was found.");
        }

        // Check access to view content.
        if ($fieldableContent instanceof Content && !$this->authorizationChecker->isGranted(ContentVoter::VIEW, $fieldableContent)) {
            throw new ContentAccessDeniedException("You are not allowed to view this content.");
        }

        if ($fieldableContent instanceof DomainMember && !$this->authorizationChecker->isGranted(DomainMemberVoter::VIEW, $fieldableContent)) {
            throw new ContentAccessDeniedException("You are not allowed to view this member.");
        }

        return $fieldableContent;
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
        if (empty($data) && !$field->getSettings()->not_empty) {
            return;
        }

        // Make sure, that all required fields are set.
        if (empty($data['domain']) || empty($data['content_type']) || empty($data['content'])) {
            $context->buildViolation('required')->atPath('['.$field->getIdentifier().']')->addViolation();
        } // Try to resolve the data to check if the current user is allowed to access it.
        else {
            try {
                $placeholderResolveInfo = new ResolveInfo($field->getIdentifier(), [], null, new ObjectType(['name' => '']), [], new Schema([]), [], null, null, []);
                $this->resolveGraphQLData($field, $data, new Content(), [], [], $placeholderResolveInfo);
            } catch (Exception $e) {
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

        if(empty($settings->content_type) && empty($settings->domain_member_type)) {
            $context->buildViolation('reference_type_required')->addViolation();
            return;
        }

        if(!empty($settings->content_type) && !empty($settings->domain_member_type)) {
            $context->buildViolation('reference_type_required')->addViolation();
            return;
        }

        // At the moment of validating settings, the referenced domain / content type might not be persisted if we are
        // referencing to domain we are about to create. In this case, we provide a fallback domain / content type.
        $this->referenceResolver->setFallbackFromContext($context, $settings);

        // Try to resolve content type. If it don't throw an exception, the domain and content_type exist and the user can access it.
        try {
            $domain = $this->referenceResolver->resolveDomain($settings->domain);
            $this->referenceResolver->resolveFieldable($domain, $settings);
        }
        catch (DomainAccessDeniedException $e) {
            $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();
        } catch (MissingOrganizationException $e) {
            $context->buildViolation('invalid_organization')->atPath('domain')->addViolation();
        } catch (MissingDomainException $e) {
            $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();
        } catch (MissingContentTypeException $e) {
            $context->buildViolation('invalid_content_type')->atPath('content_type')->addViolation();
        } catch (MissingDomainMemberTypeException $e) {
            $context->buildViolation('invalid_domain_member_type')->atPath('domain_member_type')->addViolation();
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        $settings['settings'] = $settings['settings'] ?? [];
        $settings['settings']['fields'] = $settings['settings']['fields'] ?? [];

        // normalize settings for nested fields.
        if($field && !empty($settings['settings']['fields'])) {

            /**
             * @var Fieldable $fieldable
             */
            try {
                $fieldable = $this->referenceResolver->resolveFieldable(
                    $this->referenceResolver->resolveDomain($field->getSettings()->domain),
                    $field->getSettings()
                );

                $processor = new Processor();
                $config = $processor->processConfiguration($this->tableViewConfigurationFactory->create($fieldable), ['settings' => ['fields' => $settings['settings']['fields']]]);
                $settings['settings']['fields'] = $config['fields'];

                // Template will only include assets from root fields, so we need to add any child templates to the root field.
                foreach($config['fields'] as $nestedField) {
                    if(!empty($nestedField['assets'])) {
                        $settings['assets'] = array_merge($settings['assets'], $nestedField['assets']);
                    }
                }

            } catch (Exception $e) {
                // Do nothing at this point, if we can't resolve domain.
            }
        } elseif(!empty($field->getSettings()->domain_member_type)) {
            $settings['settings']['fields']['_name'] = [
                'label' => 'Name',
                'type' => 'text',
            ];
        }
    }
}
