<?php

namespace UniteCMS\CoreBundle\SchemaType;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;

class SchemaTypeManager
{
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
     * @var int $maximumNestingLevel
     */
    private $maximumNestingLevel;

    public function __construct(int $maximumNestingLevel = 8)
    {
        $this->maximumNestingLevel = $maximumNestingLevel;
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

    public function hasSchemaType($key): bool
    {
        return array_key_exists($key, $this->schemaTypes);
    }

    /**
     * Creates a new GraphQL schema with all registered types.
     *
     * @param Domain $domain, The Domain to create the schema for.
     * @param string|ObjectType|UnionType $query, The root query object. If string, $this>>getSchemaType() will be called.
     * @param string|InputType|UnionType $mutation, The root mutation object. If string, $this>>getSchemaType() will be called.
     * @return Schema
     */
    public function createSchema(Domain $domain, $query, $mutation = null) : Schema {

        $manager = $this;
        $query = is_string($query) ? $this->getSchemaType($query, $domain) : $query;
        $mutation = $mutation ? (is_string($mutation) ? $this->getSchemaType($mutation, $domain) : $mutation) : null;

        return new Schema([
            'query' => $query,
            // At the moment only content (and not setting) can be mutated.
            'mutation' => $domain->getContentTypes()->count() > 0 ? $mutation : null,

            'typeLoader' => function ($name) use ($manager, $domain) {
                return $manager->getSchemaType($name, $domain);
            },
            'types' => function() use ($manager)  {
                return $manager->getNonDetectableSchemaTypes();
            }
        ]);
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
        if (!$schemaType instanceof InputObjectType && !$schemaType instanceof ObjectType && !$schemaType instanceof InterfaceType && !$schemaType instanceof UnionType && !$schemaType instanceof ListOfType) {
            throw new \InvalidArgumentException(
                'Schema type must be of type '.ObjectType::class.' or '.InputObjectType::class.' or '.InterfaceType::class.' or '.UnionType::class.' or '.ListOfType::class
            );
        }

        if (!isset($this->schemaTypes[$schemaType->name])) {
            $this->schemaTypes[$schemaType->name] = $schemaType;
        }

        if(!$detectable) {
            $this->nonDetectableSchemaTypes[$schemaType->name] = $schemaType;
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
}
