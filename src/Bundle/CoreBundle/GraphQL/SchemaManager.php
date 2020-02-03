<?php


namespace UniteCMS\CoreBundle\GraphQL;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\Printer;
use GraphQL\Server\RequestError;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Exception\ConstraintViolationsException;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\Scalar\ScalarResolverInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\Type\TypeResolverInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Extender\SchemaExtenderInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Modifier\SchemaModifierInterface;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaExtender;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\HttpFoundation\Request;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;
use UniteCMS\CoreBundle\ContentType\UserType;

class SchemaManager
{
    const UNITE_CMS_ROOT_SCHEMA = __DIR__ . '/../Resources/GraphQL/Schema/root-schema.graphql';

    // From https://github.com/graphql/graphql-js/blob/master/src/utilities/assertValidName.js
    const GRAPHQL_NAME_REGEX = '/^[_a-zA-Z][_a-zA-Z0-9]*$/';

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var DocumentNode
     */
    protected $baseSchemaDefinition = null;

    /**
     * @var Schema
     */
    protected $cacheableBaseSchema = null;

    /**
     * @var DocumentNode
     */
    protected $cacheableSchema = null;

    /**
     * @var Schema
     */
    protected $executableSchema = null;

    /**
     * @var SchemaProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var SchemaExtenderInterface[]
     */
    protected $beforeTypeExtenders = [];

    /**
     * @var SchemaExtenderInterface[]
     */
    protected $afterTypeExtenders = [];

    /**
     * @var SchemaModifierInterface[]
     */
    protected $modifiers = [];

    /**
     * @var FieldResolverInterface[]
     */
    protected $fieldResolvers = [];

    /**
     * @var TypeResolverInterface[]
     */
    protected $typeResolvers = [];

    /**
     * @var ScalarResolverInterface[]
     */
    protected $scalarResolvers = [];

    public function __construct(DomainManager $domainManager, ValidatorInterface $validator, LoggerInterface $logger)
    {
        $this->domainManager = $domainManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @param SchemaProviderInterface $provider
     * @return $this
     */
    public function registerProvider(SchemaProviderInterface $provider) : self {
        if(!in_array($provider, $this->providers)) {
            $this->providers[] = $provider;
        }

        return $this;
    }

    /**
     * @param SchemaExtenderInterface $extender
     * @param string $position
     *
     * @return $this
     */
    public function registerExtender(SchemaExtenderInterface $extender, string $position = SchemaExtenderInterface::EXTENDER_AFTER) : self {
        switch ($position) {
            case SchemaExtenderInterface::EXTENDER_BEFORE:
                $this->beforeTypeExtenders[] = $extender;
                break;

            case SchemaExtenderInterface::EXTENDER_AFTER:
                $this->afterTypeExtenders[] = $extender;
                break;
        }

        return $this;
    }

    /**
     * @param SchemaModifierInterface $modifier
     * @return $this
     */
    public function registerModifier(SchemaModifierInterface $modifier) : self {
        if(!in_array($modifier, $this->modifiers)) {
            $this->modifiers[] = $modifier;
        }

        return $this;
    }

    /**
     * @param FieldResolverInterface $resolver
     * @return $this
     */
    public function registerFieldResolver(FieldResolverInterface $resolver) : self {
        if(!in_array($resolver, $this->fieldResolvers)) {
            $this->fieldResolvers[] = $resolver;
        }

        return $this;
    }

    /**
     * @param TypeResolverInterface $resolver
     * @return $this
     */
    public function registerTypeResolver(TypeResolverInterface $resolver) : self {
        if(!in_array($resolver, $this->typeResolvers)) {
            $this->typeResolvers[] = $resolver;
        }

        return $this;
    }

    /**
     * @param ScalarResolverInterface $resolver
     * @return $this
     */
    public function registerScalarResolver(ScalarResolverInterface $resolver) : self {
        if(!in_array($resolver, $this->scalarResolvers)) {
            $this->scalarResolvers[] = $resolver;
        }

        return $this;
    }

    /**
     * @param array $resolvers
     * @param array $typeConfig
     * @param $typeDefinitionNode
     *
     * @return array
     */
    protected function getSupportedResolvers(array $resolvers, array $typeConfig, $typeDefinitionNode) : array{
        $supportedResolvers = [];
        foreach ($resolvers as $resolver) {
            if ($resolver->supports($typeConfig['name'], $typeDefinitionNode)) {
                $supportedResolvers[] = $resolver;
            }
        }
        return $supportedResolvers;
    }

    /**
     * @param array $typeConfig
     * @param $typeDefinitionNode
     *
     * @return mixed
     */
    protected function decorateObjectType(array $typeConfig, $typeDefinitionNode) {

        $resolvers = $this->getSupportedResolvers($this->fieldResolvers, $typeConfig, $typeDefinitionNode);

        if(count($resolvers) === 1) {
            $typeConfig['resolveField'] = [$resolvers[0], 'resolve'];
        } elseif(count($resolvers) > 1) {
            $typeConfig['resolveField'] = function($value, $args, $context, ResolveInfo $info) use ($resolvers) {
                foreach($resolvers as $resolver) {
                    $result = $resolver->resolve($value, $args, $context, $info);
                    if($result !== null) {
                        return $result;
                    }
                }
                return null;
            };
        }

        return $typeConfig;
    }

    /**
     * @param $typeConfig
     * @param $typeDefinitionNode
     *
     * @return array
     */
    protected function decorateAbstractType(array $typeConfig, $typeDefinitionNode) {

        $resolvers = $this->getSupportedResolvers($this->typeResolvers, $typeConfig, $typeDefinitionNode);

        if(count($resolvers) === 1) {
            $typeConfig['resolveType'] = [$resolvers[0], 'resolve'];
        } elseif(count($resolvers) > 1) {
            $typeConfig['resolveType'] = function($value, $context, ResolveInfo $info) use ($resolvers) {
                foreach($resolvers as $resolver) {
                    $result = $resolver->resolve($value, $context, $info);
                    if($result !== null) {
                        return $result;
                    }
                }
                return null;
            };
        }

        return $typeConfig;
    }

    /**
     * @param array $typeConfig
     * @param $typeDefinitionNode
     *
     * @return array
     */
    protected function decorateScalarType(array $typeConfig, $typeDefinitionNode) {

        $resolvers = $this->getSupportedResolvers($this->scalarResolvers, $typeConfig, $typeDefinitionNode);

        if(count($resolvers) === 1) {
            $typeConfig['serialize'] = [$resolvers[0], 'serialize'];
            $typeConfig['parseValue'] = [$resolvers[0], 'parseValue'];
            $typeConfig['parseLiteral'] = [$resolvers[0], 'parseLiteral'];
        } elseif(count($resolvers) > 1) {
            $typeConfig['serialize'] = function($value) use ($resolvers) {
                foreach($resolvers as $resolver) {
                    $result = $resolver->serialize($value);
                    if($result !== null) {
                        return $result;
                    }
                }
                return null;
            };
            $typeConfig['parseValue'] = function($value) use ($resolvers) {
                foreach($resolvers as $resolver) {
                    $result = $resolver->parseValue($value);
                    if($result !== null) {
                        return $result;
                    }
                }
                return null;
            };
            $typeConfig['parseLiteral'] = function($valueNode, array $variables = null) use ($resolvers) {
                foreach($resolvers as $resolver) {
                    $result = $resolver->parseLiteral($valueNode, $variables);
                    if($result !== null) {
                        return $result;
                    }
                }
                return null;
            };
        }

        return $typeConfig;
    }

    /**
     * @param bool $forceFresh
     *
     * @return Schema
     * @throws SyntaxError
     */
    public function buildBaseSchema(bool $forceFresh = false) : Schema {

        // TODO: Cache + load from Cache + load generateContentTypes types from cache
        // AST::fromArray

        // If the schema is already in memory, use it from there.
        if(!$forceFresh && $this->cacheableBaseSchema && $this->baseSchemaDefinition) {
            return $this->cacheableBaseSchema;
        }

        // Init with graphQL schema.
        $schemaDefinition = '';
        foreach ($this->providers as $provider) {
            $schemaDefinition .= $provider->extend() . "\n";
        }

        $schemaDefinition .= join("\n", $this->domainManager->current()->getCompleteSchema());
        $this->baseSchemaDefinition = Parser::parse($schemaDefinition);
        $this->cacheableBaseSchema = BuildSchema::build($this->baseSchemaDefinition);
        return $this->cacheableBaseSchema;
    }

    /**
     * @param bool $forceFresh
     *
     * @param ExecutionContext|null $context
     * @return DocumentNode
     * @throws Error
     * @throws SyntaxError
     */
    public function buildCacheableSchema(bool $forceFresh = false, ?ExecutionContext $context = null) : DocumentNode {

        $context = $context ?? new ExecutionContext();

        // TODO: Cache + load from Cache + load generateContentTypes types from cache
        // AST::fromArray

        // If the schema is already in memory, use it from there.
        if(!$forceFresh && $this->cacheableSchema) {
            return $this->cacheableSchema;
        }

        // Build base schema from domain and providers.
        $schema = $this->buildBaseSchema($forceFresh);

        // Execute before type extenders.
        foreach($this->beforeTypeExtenders as $extender) {
            if($extension = $extender->extend($schema, $context)) {
                $parameters = $this->domainManager->getGlobalParameters() + $this->domainManager->current()->getParameters();
                $extension = Util::replaceSchemaParameters($extension, $parameters);
                $schema = SchemaExtender::extend($schema, Parser::parse($extension));
            }
        }

        // Generate content types based on schema and validate it.
        $violations = $this->validator->validate(
            $this->generateContentTypes($schema)
        );

        if(count($violations) > 0) {
            throw new ConstraintViolationsException($violations);
        }

        // Execute after type extenders.
        foreach($this->afterTypeExtenders as $extender) {
            if($extension = $extender->extend($schema, $context)) {
                $parameters = $this->domainManager->getGlobalParameters() + $this->domainManager->current()->getParameters();
                $extension = Util::replaceSchemaParameters($extension, $parameters);
                $schema = SchemaExtender::extend($schema, Parser::parse($extension));
            }
        }

        $this->cacheableSchema = Parser::parse(SchemaPrinter::doPrint($schema));

        // Execute schema modifiers after schema was built.
        foreach($this->modifiers as $modifier) {
            $modifier->modify($this->cacheableSchema, $schema, $context);
        }

        return $this->cacheableSchema;
    }

    /**
     * @param bool $forceFresh
     *
     * @param ExecutionContext|null $context
     * @return Schema
     * @throws Error
     * @throws SyntaxError
     */
    public function buildExecutableSchema(bool $forceFresh = false, ?ExecutionContext $context = null) : Schema {

        $context = $context ?? new ExecutionContext();

        if(!$forceFresh && $this->executableSchema) {
            return $this->executableSchema;
        }

        $this->executableSchema = BuildSchema::build($this->buildCacheableSchema($forceFresh, $context), function(array $typeConfig, $typeDefinitionNode) {

            // Resolve GraphQL objects.
            if($typeDefinitionNode instanceof ObjectTypeDefinitionNode) {
                $typeConfig = $this->decorateObjectType($typeConfig, $typeDefinitionNode);
            }

            // Resolve GraphQL union and interface types.
            else if ($typeDefinitionNode instanceof UnionTypeDefinitionNode || $typeDefinitionNode instanceof InterfaceTypeDefinitionNode) {
                $typeConfig = $this->decorateAbstractType($typeConfig, $typeDefinitionNode);
            }

            // Resolve GraphQL scalars.
            else if($typeDefinitionNode instanceof ScalarTypeDefinitionNode) {
                $typeConfig = $this->decorateScalarType($typeConfig, $typeDefinitionNode);
            }

            return $typeConfig;
        });

        return $this->executableSchema;
    }

    /**
     * @param string $query
     * @param array $args
     * @param null|ExecutionContext $context
     *
     * @param bool $forceFresh
     *
     * @return ExecutionResult
     * @throws Error
     * @throws SyntaxError
     */
    public function execute(string $query, array $args = [], ?ExecutionContext $context = null, bool $forceFresh = false) : ExecutionResult {

        $context = $context ?? new ExecutionContext();

        $schema = $this->buildExecutableSchema($forceFresh, $context);
        return GraphQL::executeQuery($schema, $query, null, $context, $args)
            ->setErrorFormatter([ErrorFormatter::class, 'createFromException'])->setErrorsHandler([$this, 'handleErrors']);
    }

    /**
     * @param string $name
     * @param array $fragments
     * @param array $args
     * @param null $context
     * @param bool $forceFresh
     *
     * @return ExecutionResult
     * @throws Error
     * @throws SyntaxError
     */
    public function executeOperation(string $name, array $fragments = [], array $args = [], $context = null, bool $forceFresh = false) : ExecutionResult {

        $fragmentsQuery = '';
        $query = '';

        $this->buildBaseSchema($forceFresh);

        foreach($this->baseSchemaDefinition->definitions as $definition) {
            if($definition instanceof OperationDefinitionNode && $definition->name->value === $name) {
                $query .= Printer::doPrint($definition);
            }
            if($definition instanceof FragmentDefinitionNode && in_array($definition->name->value, $fragments)) {
                $fragmentsQuery .= Printer::doPrint($definition);
            }
        }

        if($query) {
            return $this->execute($fragmentsQuery . $query, $args, $context, $forceFresh);
        }

        throw new InvalidArgumentException(sprintf('Operation with name "%s" was not found in your schema.', $name));
    }

    /**
     * @param Request $request
     * @param bool $debug
     * @param null|ExecutionContext $context
     *
     * @param bool $forceFresh
     *
     * @return ExecutionResult
     * @throws Error
     * @throws SyntaxError
     * @throws RequestError
     */
    public function executeRequest(Request $request, bool $debug = false, ?ExecutionContext $context = null, bool $forceFresh = false) : ExecutionResult {

        $context = $context ?? new ExecutionContext();

        $server = new StandardServer(ServerConfig::create()
            ->setSchema($this->buildExecutableSchema($forceFresh))
            ->setQueryBatching(true)
            ->setDebug($debug)
            ->setContext($context)
        );

        $serverHelper = new Helper();
        return $server->executeRequest(
            $serverHelper->parseRequestParams(
                $request->getMethod(),
                json_decode($request->getContent(), true),
                $request->request->all()
            )
        )->setErrorFormatter([ErrorFormatter::class, 'createFromException'])->setErrorsHandler([$this, 'handleErrors']);
    }

    /**
     * @return DocumentNode
     * @throws SyntaxError
     */
    public function getBaseSchemaDefinition() : DocumentNode {
        $this->buildBaseSchema();
        return $this->baseSchemaDefinition;
    }

    /**
     * Creates or updates the current domain's ContentTypeManager and returns it.
     *
     * @param Schema $schema
     * @return ContentTypeManager
     * @throws Error
     */
    protected function generateContentTypes(Schema $schema) : ContentTypeManager {

        /**
         * @var InterfaceType $uniteContent
         */
        $uniteContent = $schema->getType('UniteContent');

        /**
         * @var InterfaceType $uniteSingleContent
         */
        $uniteSingleContent = $schema->getType('UniteSingleContent');

        /**
         * @var InterfaceType $uniteContentEmbed
         */
        $uniteContentEmbed = $schema->getType('UniteEmbeddedContent');

        /**
         * @var InterfaceType $uniteUser
         */
        $uniteUser = $schema->getType('UniteUser');

        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();

        // Fill content type manager from graphql objects.
        foreach($schema->getTypeMap() as $key => $type) {
            if($type instanceof ObjectType) {

                // Register content type in content type manager.
                if($type->implementsInterface($uniteContent)){
                    $contentTypeManager->registerContentType(ContentType::fromObjectType($type, $this->domainManager->getIsAdminExpression()));
                }

                // Register single content type in content type manager.
                if($type->implementsInterface($uniteSingleContent)){
                    $contentTypeManager->registerSingleContentType(ContentType::fromObjectType($type, $this->domainManager->getIsAdminExpression()));
                }

                // Register embedded content type in content type manager.
                if($type->implementsInterface($uniteContentEmbed)){
                    $contentTypeManager->registerEmbeddedContentType(ContentType::fromObjectType($type, $this->domainManager->getIsAdminExpression()));
                }

                // Register user content type in content type manager.
                if($type->implementsInterface($uniteUser)){
                    $contentTypeManager->registerUserType(UserType::fromObjectType($type, $this->domainManager->getIsAdminExpression()));
                }
            }
        }

        return $contentTypeManager;
    }

    /**
     * @param array $errors
     * @param callable $formatter
     *
     * @return array
     */
    public function handleErrors(array $errors, callable $formatter) {

        // All errors that cannot be shown to the user should be logged somewhere.
        foreach($errors as $error) {
            if($error instanceof Throwable) {
                if (!$error instanceof ClientAware || !$error->isClientSafe()) {
                    $this->logger->error($error->getMessage(), ['e' => $error]);
                }
            }
        }

        return array_map($formatter, $errors);
    }
}
