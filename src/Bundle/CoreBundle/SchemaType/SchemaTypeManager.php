<?php

namespace UniteCMS\CoreBundle\SchemaType;

use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;

class SchemaTypeManager
{
    const CACHE_PREFIX = 'unite.cms.graphql.schema';

    /**
     * @var ObjectType|InputObjectType|InterfaceType|UnionType[]
     */
    private $schemaTypes = [];

    /**
     * @var ObjectType|InputObjectType|InterfaceType|UnionType[]
     */
    private $nonDetectableSchemaTypes = [];

    /**
     * @var SchemaTypeFactoryInterface[]
     */
    private $schemaTypeFactories = [];

    /**
     * @var SchemaTypeAlterationInterface[]
     */
    private $schemaTypeAlterations = [];

    /**
     * @var int $maximumNestingLevel
     */
    private $maximumNestingLevel;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var array $domainMapping
     */
    protected $domainMapping = [];

    public function __construct(int $maximumNestingLevel = 8, CacheInterface $cache, Security $security)
    {
        $this->maximumNestingLevel = $maximumNestingLevel;
        $this->cache = $cache;
        $this->security = $security;
    }

    public function getMaximumNestingLevel() : int {
        return $this->maximumNestingLevel;
    }

    /**
     * @return ObjectType|InputObjectType|InterfaceType|UnionType[]
     */
    public function getSchemaTypes(): array
    {
        return $this->schemaTypes;
    }

    /**
     * @return ObjectType|InputObjectType|InterfaceType|UnionType[]
     */
    public function getNonDetectableSchemaTypes(): array
    {
        return $this->nonDetectableSchemaTypes;
    }

    /**
     * @return SchemaTypeFactoryInterface[]
     */
    public function getSchemaTypeFactories(): array
    {
        return $this->schemaTypeFactories;
    }

    /**
     * @return SchemaTypeAlterationInterface[]
     */
    public function getSchemaTypeAlterations(): array
    {
        return $this->schemaTypeAlterations;
    }

    public function hasSchemaType($key): bool
    {
        return array_key_exists($key, $this->schemaTypes);
    }

    /**
     * Creates a new GraphQL schema with all registered types.
     *
     * @param Domain $domain , The Domain to create the schema for.
     * @param string|ObjectType|UnionType $query , The root query object. If string, $this>>getSchemaType() will be called.
     * @param string|InputType|UnionType $mutation , The root mutation object. If string, $this>>getSchemaType() will be called.
     * @param bool $forceFreshGeneration , if set to true will not use a schema from cache but generate a fresh one.
     * @return Schema
     * @throws \GraphQL\Error\SyntaxError
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Cache\CacheException
     */
    public function createSchema(Domain $domain, $query, $mutation = null, $forceFreshGeneration = false) : Schema {

        $manager = $this;
        $user = $this->security->getUser();
        $cacheKey = implode('.', [
            static::CACHE_PREFIX,
            $domain->getOrganization()->getIdentifier(),
            $domain->getIdentifier(),
            ($user ? $user->getId() : 'anon'),
        ]);

        $cacheMetadata = [
            ItemInterface::METADATA_TAGS => [
                static::CACHE_PREFIX,
                implode('.', [static::CACHE_PREFIX, $domain->getOrganization()->getIdentifier()]),
                implode('.', [static::CACHE_PREFIX, $domain->getOrganization()->getIdentifier(), $domain->getIdentifier()]),
                implode('.', [static::CACHE_PREFIX . '_user' . ($user ? $user->getId() : 'anon')]),
            ],
        ];

        $cachedTypes = $this->cache->get($cacheKey,

            // Create a fresh schema and save it to cache.
            function() use ($query, $mutation, $manager, $domain) {

                $this->domainMapping = [];
                $query = is_string($query) ? $this->getSchemaType($query, $domain) : $query;
                $mutation = ($domain->getContentTypes()->count() > 0 && $mutation) ? (is_string($mutation) ? $this->getSchemaType($mutation, $domain) : $mutation) : null;

                $schema = new Schema([
                    'query' => $query,
                    // At the moment only content (and not setting) can be mutated.
                    'mutation' => $mutation,

                    'typeLoader' => function ($name) use ($manager, $domain) {
                        return $manager->getSchemaType($name, $domain);
                    },
                    'types' => function() use ($manager)  {
                        return $manager->getNonDetectableSchemaTypes();
                    }
                ]);

                $schemaDefinition = SchemaPrinter::doPrint($schema);

                // If we have an empty mutation type, remove it to avoid parsing problems.
                $schemaDefinition = str_replace('type Mutation {

}

', '', $schemaDefinition);

                $types = AST::toArray(Parser::parse($schemaDefinition));

                return [
                    'domainMapping' => $this->domainMapping,
                    'types' => $types,
                ];
            },

            // If $forceFreshGeneration = true, expire cache, else use default beta (1.0).
            ($forceFreshGeneration ? INF : 1.0),

            // Set cache tags
            $cacheMetadata
        );

        $astArray = $cachedTypes['types'];
        $domainMapping = $cachedTypes['domainMapping'];

        // Build the schema from cached array.
        return BuildSchema::build(AST::fromArray($astArray), function($typeConfig, $typeDefinitionNode) use ($manager, $domain, $domainMapping) {

            // We need to enrich object enum and interface types.
            if(!in_array($typeDefinitionNode->kind, [
                NodeKind::OBJECT_TYPE_DEFINITION,
                NodeKind::ENUM_TYPE_DEFINITION,
                NodeKind::INTERFACE_TYPE_DEFINITION,
            ])) {
                return $typeConfig;
            }

            $nameParts = preg_split('/(?=[A-Z])/', $typeConfig['name'], -1, PREG_SPLIT_NO_EMPTY);
            $lastPart = array_pop($nameParts);
            $nestingLevel = substr($lastPart, 0, 5) === 'Level' ? substr($lastPart, 5) : 0;

            if(array_key_exists($typeConfig['name'], $domainMapping)) {
                if($domainMapping[$typeConfig['name']] !== $domain->getIdentifier()) {
                    $domainIdentifier = $domainMapping[$typeConfig['name']];
                    $domain = $domain->getOrganization()->getDomains()->filter(function(Domain $d) use ($domainIdentifier) {
                        return $d->getIdentifier() === $domainIdentifier;
                    })->first();
                }
            }

            $fullType = $manager->getSchemaType($typeConfig['name'], $domain, $nestingLevel);

            if($fullType instanceof ObjectType) {
                $typeConfig['resolveField'] = $fullType->config['resolveField'];
            }

            if($fullType instanceof InterfaceType) {
                $typeConfig['resolveType'] = $fullType->config['resolveType'];
            }

            if($fullType instanceof EnumType) {
                foreach($typeConfig['values'] as $key => $value) {
                    $typeConfig['values'][$key]['value'] = $fullType->getValue($key)->value;
                }
            }

            return $typeConfig;
        });
    }

    /**
     * Returns the named schema type. If schema type was not found all registered factories get asked if they can
     * create the schema. If no schema was found and no schema could be created, an \InvalidArgumentException will be
     * thrown.
     *
     * @param $key
     * @param Domain $domain
     * @param int $nestingLevel
     *
     * @return ObjectType|InputObjectType|InterfaceType|UnionType
     */
    public function getSchemaType($key, Domain $domain = null, $nestingLevel = 0)
    {
        if($domain) {
            $this->domainMapping[$key] = $domain->getIdentifier();
        }

        if ($nestingLevel >= $this->getMaximumNestingLevel()) {
            $key = 'MaximumNestingLevel';
        }

        if (!$this->hasSchemaType($key)) {
            foreach ($this->schemaTypeFactories as $schemaTypeFactory) {
                if ($schemaTypeFactory->supports($key)) {
                    $this->registerSchemaType(
                        $schemaTypeFactory->createSchemaType($this, $nestingLevel, $domain, $key)
                    );
                    break;
                }
            }
        }

        if (!$this->hasSchemaType($key)) {
            throw new \InvalidArgumentException("The schema type: '$key' was not found.");
        }

        return $this->schemaTypes[$key];
    }

    /**
     * @param Type $schemaType
     *
     * @param bool $detectable, if set to false, this type type will always get added to schema. Otherwise types will
     *   get automatically detected during query evaluation.
     *
     * @return SchemaTypeManager
     */
    public function registerSchemaType(Type $schemaType, $detectable = true)
    {
        if (!$schemaType instanceof InputObjectType && !$schemaType instanceof ObjectType && !$schemaType instanceof InterfaceType && !$schemaType instanceof UnionType && !$schemaType instanceof ListOfType && !$schemaType instanceof EnumType) {
            throw new \InvalidArgumentException(
                'Schema type must be of type '.ObjectType::class.' or '.InputObjectType::class.' or '.InterfaceType::class.' or '.UnionType::class.' or '.ListOfType::class.' or '.EnumType::class
            );
        }

        if (!isset($this->schemaTypes[$schemaType->name])) {
            $this->schemaTypes[$schemaType->name] = $schemaType;
        }

        if(!$detectable) {
            $this->nonDetectableSchemaTypes[$schemaType->name] = $schemaType;
        }

        foreach ($this->getSchemaTypeAlterations() as $schemaTypeAlteration) {
            if($schemaTypeAlteration->supports($schemaType->name)) {
                $schemaTypeAlteration->alter($schemaType);
            }
        }

        return $this;
    }

    /**
     * @param SchemaTypeFactoryInterface $schemaTypeFactory
     *
     * @return SchemaTypeManager
     */
    public function registerSchemaTypeFactory(SchemaTypeFactoryInterface $schemaTypeFactory)
    {
        if (!in_array($schemaTypeFactory, $this->schemaTypeFactories)) {
            $this->schemaTypeFactories[] = $schemaTypeFactory;
        }

        return $this;
    }

    /**
     * @param SchemaTypeAlterationInterface $schemaTypeAlteration
     *
     * @return SchemaTypeManager
     */
    public function registerSchemaTypeAlteration(SchemaTypeAlterationInterface $schemaTypeAlteration)
    {
        if (!in_array($schemaTypeAlteration, $this->schemaTypeAlterations)) {
            $this->schemaTypeAlterations[] = $schemaTypeAlteration;
        }

        return $this;
    }
}
