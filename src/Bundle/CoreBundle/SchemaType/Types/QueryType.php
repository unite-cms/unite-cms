<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Paginator;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainAccessor;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Service\GraphQLDoctrineFilterQueryBuilder;

class QueryType extends AbstractType
{


    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UniteCMSManager $uniteCMSManager
     */
    private $uniteCMSManager;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var Paginator $paginator
     */
    private $paginator;

    /**
     * @var int $maximumQueryLimit
     */
    private $maximumQueryLimit;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        EntityManager $entityManager,
        UniteCMSManager $uniteCMSManager,
        AuthorizationChecker $authorizationChecker,
        Paginator $paginator,
        int $maximumQueryLimit = 100
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->entityManager = $entityManager;
        $this->uniteCMSManager = $uniteCMSManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->paginator = $paginator;
        $this->maximumQueryLimit = $maximumQueryLimit;
        parent::__construct();
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        $fields = [];
        $fields['find'] = [
            'type' => $this->schemaTypeManager->getSchemaType('FieldableContentResult', $this->uniteCMSManager->getDomain()),
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
                    'type' => Type::listOf($this->schemaTypeManager->getSchemaType('SortInput')),
                    'description' => 'Set one or many fields to sort by.',
                ],
                'filter' => [
                    'type' => $this->schemaTypeManager->getSchemaType('FilterInput'),
                    'description' => 'Set one optional filter condition.',
                ],
                'types' => [
                    'type' => Type::listOf(Type::string()),
                    'description' => 'Set all content types to get content from. With this option you can get content from multiple content types.',
                ],
                'deleted' => [
                    'type' => Type::boolean(),
                    'description' => 'Also show deleted entries. Only user who can also update content can view deleted content.',
                    'defaultValue' => false,
                ],
            ],
        ];

        // Append Content types.
        foreach ($this->uniteCMSManager->getDomain()->getContentTypes() as $contentType) {

            // If the current user is not allowed to access this content type, skip adding a get and find action.
            if(!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
                continue;
            }

            $key = IdentifierNormalizer::graphQLType($contentType, '');
            $fields['get' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to get.',
                    ],
                ],
            ];

            $fields['find' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'ContentResult', $this->uniteCMSManager->getDomain()),
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
                        'type' => Type::listOf($this->schemaTypeManager->getSchemaType('SortInput')),
                        'description' => 'Set one or many fields to sort by.',
                    ],
                    'filter' => [
                        'type' => $this->schemaTypeManager->getSchemaType('FilterInput'),
                        'description' => 'Set one optional filter condition.',
                    ],
                    'deleted' => [
                        'type' => Type::boolean(),
                        'description' => 'Also show deleted entries. Only user who can also update content can view deleted content.',
                        'defaultValue' => false,
                    ],
                ],
            ];
        }

        // Append Domain Member types.
        foreach ($this->uniteCMSManager->getDomain()->getDomainMemberTypes() as $domainMemberType) {

            // If the current user is not allowed to access this content type, skip adding a get and find action.
            if(!$this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $domainMemberType)) {
                continue;
            }

            $key = IdentifierNormalizer::graphQLType($domainMemberType, '');
            $fields['get' . $key . 'Member'] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Member', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the domain member item to get.',
                    ],
                ],
            ];

            $fields['find' . $key . 'Member'] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'MemberResult', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'limit' => [
                        'type' => Type::int(),
                        'description' => 'Set maximal number of domain member items to return.',
                        'defaultValue' => 20,
                    ],
                    'page' => [
                        'type' => Type::int(),
                        'description' => 'Set the pagination page to get the domain member from.',
                        'defaultValue' => 1,
                    ],
                    'sort' => [
                        'type' => Type::listOf($this->schemaTypeManager->getSchemaType('SortInput')),
                        'description' => 'Set one or many fields to sort by.',
                    ],
                    'filter' => [
                        'type' => $this->schemaTypeManager->getSchemaType('FilterInput'),
                        'description' => 'Set one optional filter condition.',
                    ],
                ],
            ];
        }

        // Append Setting types.
        foreach ($this->uniteCMSManager->getDomain()->getSettingTypes() as $settingType) {

            // If the current user is not allowed to access this setting, skip adding a get action.
            if(!$this->authorizationChecker->isGranted(SettingVoter::VIEW, $settingType)) {
                continue;
            }

            $key = IdentifierNormalizer::graphQLType($settingType);
            $fields[$key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key, $this->uniteCMSManager->getDomain()),
            ];
        }

        return $fields;
    }

    /**
     * Resolve fields for this type.
     * Returns the object or scalar value for the field, define in $info.
     *
     * @param mixed $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        // Resolve single content type.
        if(substr($info->fieldName, 0, 3) == 'get') {

            $id = $args['id'];

            // Resolve get domainMember
            if(substr($info->fieldName, -strlen('Member')) === 'Member' && $info->fieldName !== 'getMember') {
                if(!$domainMember = $this->entityManager->getRepository('UniteCMSCoreBundle:DomainMember')->find($id)) {
                    throw new UserError("DomainMember with id '$id' was not found.");
                }

                if ($domainMember && !$this->authorizationChecker->isGranted(DomainMemberVoter::VIEW, $domainMember)) {
                    throw new UserError("You are not allowed to view domain member with id '$id'.");
                }

                return $domainMember;
            }

            // Resolve get content
            else {
                if(!$content = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->find($id)) {
                    throw new UserError("Content with id '$id' was not found.");
                }

                if ($content && !$this->authorizationChecker->isGranted(ContentVoter::VIEW, $content)) {
                    throw new UserError("You are not allowed to view content with id '$id'.");
                }

                return $content;
            }
        }

        // Resolve single setting type.
        elseif (substr($info->fieldName, -strlen('Setting')) === 'Setting') {

            $identifier = IdentifierNormalizer::fromGraphQLSchema($info->fieldName);

            if (!$settingType = $this->entityManager->getRepository('UniteCMSCoreBundle:SettingType')->findOneBy(['domain' => $this->uniteCMSManager->getDomain(), 'identifier' => $identifier])) {
                throw new UserError("SettingType '$identifier' was not found in domain.");
            }

            $setting = $settingType->getSetting();
            if (!$this->authorizationChecker->isGranted(SettingVoter::VIEW, $setting)) {
                throw new UserError("You are not allowed to view setting of type '$identifier'.");
            }

            return $setting;
        }

        // Resolve list content type.
        elseif(substr($info->fieldName, 0, 4) == 'find' && strlen($info->fieldName) > 4) {

            $identifier = IdentifierNormalizer::fromGraphQLFieldName($info->fieldName);
            $args['types'] = [$identifier];

            // Resolve get domainMember
            if(substr($info->fieldName, -strlen('Member')) === 'Member' && $info->fieldName !== 'findMember') {
                return $this->resolveFindDomainMember(IdentifierNormalizer::graphQLType($identifier, 'MemberResult'),  $value, $args, $context, $info);
            }

            // Resolve get content type
            else {
                return $this->resolveFindContent(IdentifierNormalizer::graphQLType($identifier, 'ContentResult'),  $value, $args, $context, $info);
            }
        }

        // Resolve generic find type
        elseif(substr($info->fieldName, 0, 4) == 'find' && strlen($info->fieldName) == 4) {
            return $this->resolveFindContent('FieldableContentResult', $value, $args, $context, $info);
        }

        return null;
    }

    /**
     * Resolve the content results.
     *
     * @param $resultType
     * @param $value
     * @param array $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function resolveFindContent($resultType, $value, array $args, $context, ResolveInfo $info) : AbstractPagination
    {
        $args['types'] = $args['types'] ?? [];
        $args['limit'] = $args['limit'] < 0 ? 0 : $args['limit'];
        $args['limit'] = $args['limit'] > $this->maximumQueryLimit ? $this->maximumQueryLimit : $args['limit'];
        $args['page'] = $args['page'] < 1 ? 1 : $args['page'];
        $args['deleted'] = $args['deleted'] ?? false;

        // Get all requested contentTypes, the user can access.
        $contentTypes = [];
        foreach($this->entityManager->getRepository('UniteCMSCoreBundle:ContentType')->findBy([
            'identifier' => $args['types'],
            'domain' => $this->uniteCMSManager->getDomain(),
        ]) as $contentType) {
            if ($this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
                $contentTypes[] = $contentType;
            }
        }


        // Get content from all contentTypes
        $contentEntityFields = $this->entityManager->getClassMetadata(Content::class)->getFieldNames();
        $contentQuery = $this->entityManager->getRepository(
            'UniteCMSCoreBundle:Content'
        )->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType IN (:contentTypes)')
            ->setParameter(':contentTypes', $contentTypes);

        // Sorting by nested data attributes is not possible with knp paginator, so we need to do it manually.
        if (!empty($args['sort'])) {
            foreach ($args['sort'] as $sort) {

                $key = $sort['field'];
                $order = $sort['order'];

                // We can sort by a entity field or a nested data field.
                $sortField = in_array($key, $contentEntityFields) ? 'c.'.$key : "JSON_EXTRACT(c.data, '$.$key')";

                if(!empty($sort['ignore_case'])) {
                    $sortField = 'LOWER(' . $sortField . ')';
                }

                $contentQuery->addOrderBy($sortField, $order);
            }
        }

        // Adding where filter to the query.
        if (!empty($args['filter'])) {


            // The filter array can contain a direct filter or multiple nested AND or OR filters. But only one of this cases.

            // TODO: Replace field names with nested field selectors.

            $a = new GraphQLDoctrineFilterQueryBuilder($args['filter'], $contentEntityFields, 'c');
            $contentQuery->andWhere($a->getFilter());
            foreach($a->getParameters() as $parameter => $value) {
                $contentQuery->setParameter($parameter, $value);
            }
        }

        // Also show deleted content.
        if($args['deleted']) {
            $this->entityManager->getFilters()->disable('gedmo_softdeleteable');
        }

        // Get all content in one request for all contentTypes.
        $pagination = $this->paginator->paginate($contentQuery, $args['page'], $args['limit'], ['alias' => $resultType]);

        if($args['deleted']) {
            // We need to clear content cache, so deleted entities will not be shown on next turn.
            $this->entityManager->clear(Content::class);
            $this->entityManager->getFilters()->enable('gedmo_softdeleteable');
        }

        return $pagination;
    }

    /**
     * Resolve the domain member results.
     *
     * @param $resultType
     * @param $value
     * @param array $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function resolveFindDomainMember($resultType, $value, array $args, $context, ResolveInfo $info) : AbstractPagination
    {
        $args['types'] = $args['types'] ?? [];
        $args['limit'] = $args['limit'] < 0 ? 0 : $args['limit'];
        $args['limit'] = $args['limit'] > $this->maximumQueryLimit ? $this->maximumQueryLimit : $args['limit'];
        $args['page'] = $args['page'] < 1 ? 1 : $args['page'];

        // Get all requested contentTypes, the user can access.
        $domainMemberTypes = [];
        foreach($this->entityManager->getRepository('UniteCMSCoreBundle:DomainMemberType')->findBy([
            'identifier' => $args['types'],
            'domain' => $this->uniteCMSManager->getDomain(),
        ]) as $domainMemberType) {
            if ($this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $domainMemberType)) {
                $domainMemberTypes[] = $domainMemberType;
            }
        }


        // Get content from all domainMemberTypes
        $domainMemberEntityFields = $this->entityManager->getClassMetadata(DomainMember::class)->getFieldNames();

        $contentQuery = $this->entityManager->getRepository(
            'UniteCMSCoreBundle:DomainMember'
        )->createQueryBuilder('m')
            ->select(['m'])
            ->where('m.domainMemberType IN (:domainMemberTypes)')
            ->setParameter(':domainMemberTypes', $domainMemberTypes);

        $usedJoins = [];
        $nameParts = [];

        foreach($this->entityManager->getClassMetadata(DomainAccessor::class)->discriminatorMap as $key => $class) {
            if(!in_array($key, $usedJoins)) {
                $contentQuery->leftJoin($class, $key, 'WITH', $key.'.id = m.accessor');
                $usedJoins[] = $key;
            }
            $nameParts[] = sprintf('%s.%s', $key, $class::getNameField());
        }

        $contentQuery->addSelect(sprintf('COALESCE(%s) as _name', join(', ', $nameParts)));

        // Sorting by nested data attributes is not possible with knp paginator, so we need to do it manually.
        if (!empty($args['sort'])) {
            foreach ($args['sort'] as $sort) {

                $key = $sort['field'];
                $order = $sort['order'];
                $sortFields = [];

                // if we sort by a content field.
                if (in_array($key, $domainMemberEntityFields)) {
                    $sortFields[] = 'm.' . $key;

                // if we sort by special _name field
                } elseif($key === '_name') {
                    $sortFields[] = '_name';

                // if we sort by a nested content data field.
                } else {
                    $sortFields[] = "JSON_EXTRACT(c.data, '$.$key')";
                }

                foreach($sortFields as $sortField) {
                    if(!empty($sort['ignore_case'])) {
                        $sortField = 'LOWER(' . $sortField . ')';
                    }
                    $contentQuery->addOrderBy($sortField, $order);
                }
            }
        }

        // Adding where filter to the query.
        if (!empty($args['filter'])) {

            // Domain members have a reference to domain accessors, that is an abstract entity and implemented by different entities.
            // In order to allow to filter by name, we need to do different joins.
            $accessorJoins = [];
            $accessorNameFields = [];
            foreach($this->entityManager->getClassMetadata(DomainAccessor::class)->discriminatorMap as $key => $class) {
                $accessorJoins[] = new Join(Join::LEFT_JOIN, $class, $key, 'WITH', $key . '.id = m.accessor');
                $accessorNameFields[] = $key . '.' . $class::getNameField();
            }


            // The filter array can contain a direct filter or multiple nested AND or OR filters. But only one of this cases.
            $a = new GraphQLDoctrineFilterQueryBuilder($args['filter'], $domainMemberEntityFields, 'm', $accessorJoins, function(array $filterInput) use ($accessorNameFields) {
                if(!empty($filterInput['field']) && $filterInput['field'] === '_name') {
                    $filter = ['OR' => []];
                    foreach($accessorNameFields as $field) {
                        $filter['OR'][] = [
                            'field' => $field,
                            'operator' => $filterInput['operator'],
                            'value' => $filterInput['value'],
                        ];
                    }
                    return $filter;
                }
                return $filterInput;
            });
            $contentQuery->andWhere($a->getFilter());

            foreach($a->getParameters() as $parameter => $value) {
                $contentQuery->setParameter($parameter, $value);
            }

            foreach($a->getJoins() as $join) {
                if(!in_array($join->getAlias(), $usedJoins)) {
                    $contentQuery->leftJoin($join->getJoin(), $join->getAlias(), $join->getConditionType(), $join->getCondition(), $join->getIndexBy());
                    $usedJoins[] = $join->getAlias();
                }
            }
        }

        // Get all content in one request for all contentTypes.
        return $this->paginator->paginate($contentQuery, $args['page'], $args['limit'], ['alias' => $resultType]);
    }
}
