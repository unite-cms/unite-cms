<?php


namespace UniteCMS\CoreBundle\GraphQL;

use GraphQL\Language\AST\ScalarTypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Exception\InvalidContentTypesException;
use UniteCMS\CoreBundle\GraphQL\Schema\Extender\SchemaExtenderInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Modifier\SchemaModifierInterface;
use UniteCMS\CoreBundle\GraphQL\Resolver\FieldResolverInterface;
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
use UniteCMS\CoreBundle\UserType\UserType;

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

    public function __construct(DomainManager $domainManager, ValidatorInterface $validator)
    {
        $this->domainManager = $domainManager;
        $this->validator = $validator;
    }

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
    protected $resolvers = [];

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
    public function registerResolver(FieldResolverInterface $resolver) : self {
        if(!in_array($resolver, $this->resolvers)) {
            $this->resolvers[] = $resolver;
        }

        return $this;
    }

    /**
     * @return DocumentNode
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    public function buildCacheableSchema() : DocumentNode {

        // TODO: Cache + load from Cache + load generateContentTypes types from cache
        // AST::fromArray

        // If the schema is already in memory, use it from there.
        if($this->cacheableSchema) {
            return $this->cacheableSchema;
        }

        // Init with graphQL schema.
        $schemaDefinition = '';
        foreach ($this->providers as $provider) {
            $schemaDefinition .= $provider->extend() . "\n";
        }

        $schemaDefinition .= join("\n", $this->domainManager->current()->getSchema());
        $schema = BuildSchema::build($schemaDefinition);

        // Execute before type extenders.
        foreach($this->beforeTypeExtenders as $extender) {
            if($extension = $extender->extend($schema)) {
                $schema = SchemaExtender::extend($schema, Parser::parse($extension));
            }
        }

        // Generate content types based on schema and validate it.
        $errors = $this->validator->validate(
            $this->generateContentTypes($schema)
        );

        if(count($errors) > 0) {
            throw new InvalidContentTypesException($errors);
        }

        // Execute after type extenders.
        foreach($this->afterTypeExtenders as $extender) {
            if($extension = $extender->extend($schema)) {
                $schema = SchemaExtender::extend($schema, Parser::parse($extension));
            }
        }

        $this->cacheableSchema = Parser::parse(SchemaPrinter::doPrint($schema));

        // Execute schema modifiers after schema was built.
        foreach($this->modifiers as $modifier) {
            $modifier->modify($this->cacheableSchema, $schema);
        }

        return $this->cacheableSchema;
    }

    /**
     * @return \GraphQL\Type\Schema
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    public function buildExecutableSchema() : Schema {

        if($this->executableSchema) {
            return $this->executableSchema;
        }

        $this->executableSchema = BuildSchema::build($this->buildCacheableSchema(), function(array $typeConfig, $typeDefinitionNode) {
            if($typeDefinitionNode instanceof ObjectTypeDefinitionNode) {

                $supportedResolvers = [];

                foreach ($this->resolvers as $resolver) {
                    if ($resolver->supports($typeConfig['name'], $typeDefinitionNode)) {
                        $supportedResolvers[] = $resolver;
                    }
                }

                if(count($supportedResolvers) === 1) {
                    $typeConfig['resolveField'] = [$supportedResolvers[0], 'resolve'];
                } elseif(count($supportedResolvers) > 1) {
                    $typeConfig['resolveField'] = function($value, $args, $context, ResolveInfo $info) use ($supportedResolvers) {
                        foreach($supportedResolvers as $resolver) {
                            if($result = $resolver->resolve($value, $args, $context, $info)) {
                                return $result;
                            }
                        }
                        return null;
                    };
                }
            }

            else if ($typeDefinitionNode instanceof UnionTypeDefinitionNode) {
                $typeConfig['resolveType'] = function($value) {

                    // At the moment we can only resolve Unite Content
                    if($value instanceof ContentInterface) {
                        return $this->executableSchema->getType($value->getType());
                    }

                    // TODO: Allow others to resolve custom types.
                };
            }

            return $typeConfig;
        });

        return $this->executableSchema;
    }

    /**
     * @param string $query
     * @param array $args
     * @param null $context
     *
     * @return ExecutionResult
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    public function execute(string $query, array $args = [], $context = null) : ExecutionResult {
        $schema = $this->buildExecutableSchema();
        return GraphQL::executeQuery($schema, $query, null, $context, $args);
    }

    /**
     * @param Request $request
     * @param bool $debug
     * @param null $context
     *
     * @return ExecutionResult
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     * @throws \GraphQL\Server\RequestError
     */
    public function executeRequest(Request $request, bool $debug = false, $context = null) : ExecutionResult {

        $server = new StandardServer(ServerConfig::create()
            ->setSchema($this->buildExecutableSchema())
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
        );
    }

    /**
     * Creates or updates the current domain's ContentTypeManager and returns it.
     *
     * @param Schema $schema
     * @return \UniteCMS\CoreBundle\ContentType\ContentTypeManager
     */
    protected function generateContentTypes(Schema $schema) : ContentTypeManager {

        /**
         * @var InterfaceType $uniteContent
         */
        $uniteContent = $schema->getType('UniteContent');

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
                    $contentTypeManager->registerContentType(ContentType::fromObjectType($type));
                }

                // Register embedded content type in content type manager.
                if($type->implementsInterface($uniteContentEmbed)){
                    $contentTypeManager->registerEmbeddedContentType(ContentType::fromObjectType($type));
                }

                // Register user content type in content type manager.
                if($type->implementsInterface($uniteUser)){
                    $contentTypeManager->registerUserType(UserType::fromObjectType($type));
                }
            }
        }

        return $contentTypeManager;
    }
}
