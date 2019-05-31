<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.11.18
 * Time: 11:59
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Exception\DomainAccessDeniedException;
use UniteCMS\CoreBundle\Exception\MissingContentTypeException;
use UniteCMS\CoreBundle\Exception\MissingDomainException;
use UniteCMS\CoreBundle\Exception\MissingFieldException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Form\ReferenceOfType;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;
use UniteCMS\CoreBundle\Service\ReferenceResolver;
use UniteCMS\CoreBundle\Service\UniteCMSManager;

class ReferenceOfFieldType extends FieldType
{
    const TYPE = "reference_of";
    const FORM_TYPE = ReferenceOfType::class;

    const SETTINGS = ['domain', 'content_type', 'reference_field', 'form_group'];
    const REQUIRED_SETTINGS = ['domain', 'content_type', 'reference_field'];

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var ReferenceResolver $referenceResolver
     */
    private $referenceResolver;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var PaginatorInterface $paginator
     */
    private $paginator;

    /**
     * @var int $maximumQueryLimit
     */
    private $maximumQueryLimit;

    function __construct(
        ValidatorInterface $validator,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager,
        EntityManager $entityManager,
        PaginatorInterface $paginator,
        int $maximumQueryLimit = 100
    ) {
        $this->validator = $validator;
        $this->referenceResolver = new ReferenceResolver($uniteCMSManager, $entityManager, $authorizationChecker);
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
        $this->maximumQueryLimit = $maximumQueryLimit;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $domain = $this->referenceResolver->resolveDomain($field->getSettings()->domain);
        $fieldable = $this->referenceResolver->resolveFieldable($domain, $field->getSettings());
        $reference_field = $this->referenceResolver->resolveField($fieldable, $field->getSettings()->reference_field, ReferenceFieldType::getType());

        return array_merge(parent::getFormOptions($field), [
            'view' => $fieldable instanceof ContentType ? $fieldable->getView('all') : null,
            'reference_field' => $reference_field,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        if(!$context->getObject() instanceof ContentTypeField && !$context->getObject() instanceof DomainMemberTypeField) {
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
            $ref_field_ct_setting = $field->getSettings()->content_type ?? $field->getSettings()->domain_member_type ?? null;

            /**
             * @var Fieldable $thisFieldable
             */
            $thisFieldable = $context->getObject()->getEntity();

            // Check if field references the current content type.
            if(
                (
                    $field->getSettings()->domain !== $thisFieldable->getDomain()->getIdentifier()
                    && $field->getSettings()->domain !== $thisFieldable->getDomain()->getPreviousIdentifier()
                ) || $ref_field_ct_setting !== $thisFieldable->getIdentifier()
            ) {
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

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        $domain = $this->referenceResolver->resolveDomain($field->getSettings()->domain);
        $contentType = $this->referenceResolver->resolveContentType($domain, $field->getSettings()->content_type);

        return [
            'type' => $schemaTypeManager->getSchemaType(IdentifierNormalizer::graphQLType($contentType->getIdentifier(), 'ContentResult'), $domain),
            'args' => [
                'limit' => [
                    'type' => Type::int(),
                    'description' => 'Set maximal number of content items to return.',
                    'defaultValue' => 20,
                ],
                'page' => [
                    'type' => Type::int(),
                    'description' => 'Set the pagination page to get the content from.',
                    'defaultValue' => 1,
                ],
                'sort' => [
                    'type' => Type::listOf($schemaTypeManager->getSchemaType('SortInput')),
                    'description' => 'Set one or many fields to sort by.',
                ],
                'filter' => [
                    'type' => $schemaTypeManager->getSchemaType('FilterInput'),
                    'description' => 'Set one optional filter condition.',
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        // Reference of fields does only work for content and domain member entities.
        if(!$content instanceof Content && !$content instanceof DomainMember) {
            return null;
        }

        $args['limit'] = $args['limit'] < 0 ? 0 : $args['limit'];
        $args['limit'] = $args['limit'] > $this->maximumQueryLimit ? $this->maximumQueryLimit : $args['limit'];
        $args['page'] = $args['page'] < 1 ? 1 : $args['page'];

        // Resolve content type and field.
        $domain = $this->referenceResolver->resolveDomain($field->getSettings()->domain);
        $contentType = $this->referenceResolver->resolveContentType($domain, $field->getSettings()->content_type);
        $field = $this->referenceResolver->resolveField($contentType, $field->getSettings()->reference_field, ReferenceFieldType::getType());

        // Create filter by reference field + optional filter args
        $referenceFilter = ['field' => $field->getIdentifier().'.content', 'operator' => '=', 'value' => $content->getId()];

        // If args have a content filter already set, remove it first.
        if(!empty($args['filter']['field']) && $args['filter']['field'] === $field->getIdentifier().'.content') {
            $args['filter'] = [];
        }

        $args['filter'] = empty($args['filter']) ? $referenceFilter : ['AND' => [$referenceFilter, $args['filter']]];

        // Get content for the resolved content type.
        $contentEntityFields = $this->entityManager->getClassMetadata(Content::class)->getFieldNames();
        $contentQuery = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')
            ->setParameter(':contentType', $contentType);

        // Sorting by nested data attributes is not possible with knp paginator, so we need to do it manually.
        if (!empty($args['sort'])) {
            foreach ($args['sort'] as $sort) {

                $key = $sort['field'];
                $order = $sort['order'];

                // if we sort by a content field.
                if (in_array($key, $contentEntityFields)) {
                    $contentQuery->addOrderBy('c.'.$key, $order);

                    // if we sort by a nested content data field.
                } else {
                    $contentQuery->addOrderBy("JSON_EXTRACT(c.data, '$.$key')", $order);
                }
            }
        }

        // Adding where filter to the query.
        // The filter array can contain a direct filter or multiple nested AND or OR filters. But only one of this cases.
        // TODO: Replace field names with nested field selectors.
        $a = new GraphQLDoctrineFilterQueryBuilder($args['filter'], $contentEntityFields, 'c');
        $contentQuery->andWhere($a->getFilter());
        foreach($a->getParameters() as $parameter => $value) {
            $contentQuery->setParameter($parameter, $value);
        }

        // Get all content in one request for all contentTypes.
        return $this->paginator->paginate($contentQuery, $args['page'], $args['limit'], [
            'alias' => IdentifierNormalizer::graphQLType($contentType->getIdentifier(), 'ContentResult')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        // This field does not save any data it only is an accessor to fields that are referencing this content.
        // Therefore there is no input for this field.
        return null;
    }
}
