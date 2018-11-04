<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.11.18
 * Time: 11:59
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Exception\DomainAccessDeniedException;
use UniteCMS\CoreBundle\Exception\MissingContentTypeException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingFieldException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\ReferenceOfType;
use UniteCMS\CoreBundle\Service\ReferenceResolver;
use UniteCMS\CoreBundle\Service\UniteCMSManager;

class ReferenceOfFieldType extends FieldType
{
    const TYPE = "reference_of";
    const FORM_TYPE = ReferenceOfType::class;

    const SETTINGS = ['domain', 'content_type', 'reference_field'];
    const REQUIRED_SETTINGS = ['domain', 'content_type', 'reference_field'];

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var ReferenceResolver $referenceResolver
     */
    private $referenceResolver;

    function __construct(
        ValidatorInterface $validator,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager,
        EntityManager $entityManager
    ) {
        $this->validator = $validator;
        $this->referenceResolver = new ReferenceResolver($uniteCMSManager, $entityManager, $authorizationChecker);
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        if(!$context->getObject() instanceof ContentTypeField) {
            $context->buildViolation('invalid_entity_type')->addViolation();
        }

        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if ($context->getViolations()->count() > 0) {
            return;
        }

        // At the moment of validating settings, the referenced domain / content type might not be persisted if we are
        // referencing to domain we are about to create. In this case, we provide a fallback domain / content type.
        $this->referenceResolver->setFallbackFromContext($context, $settings);

        // Try to resolve referenced Domain.
        try {
            $domain = $this->referenceResolver->resolveDomain($settings->domain);
            $contentType = $this->referenceResolver->resolveContentType($domain, $settings->content_type);
            $field = $this->referenceResolver->resolveField($contentType, $settings->reference_field, ReferenceFieldType::getType());

            /**
             * @var ContentType $thisContentType
             */
            $thisContentType = $context->getObject()->getContentType();

            // Check if field references the current content type.
            if($field->getSettings()->domain !== $thisContentType->getDomain()->getIdentifier() || $field->getSettings()->content_type !== $thisContentType->getIdentifier()) {
                $context->buildViolation('invalid_field_reference')->atPath('reference_field')->addViolation();
            }

        } catch (DomainAccessDeniedException $e) {
            $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();
        } catch (MissingOrganizationException $e) {
            $context->buildViolation('invalid_organization')->atPath('domain')->addViolation();
        } catch (MissingDomainException $e) {
            $context->buildViolation('invalid_domain')->atPath('domain')->addViolation();
        } catch (MissingContentTypeException $e) {
            $context->buildViolation('invalid_content_type')->atPath('content_type')->addViolation();
        } catch (MissingFieldException $e) {
            $context->buildViolation('invalid_field')->atPath('reference_field')->addViolation();
        }
    }
}
