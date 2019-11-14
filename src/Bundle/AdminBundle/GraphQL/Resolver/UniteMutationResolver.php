<?php


namespace UniteCMS\AdminBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Filesystem\Filesystem;
use UniteCMS\AdminBundle\Exception\NoEditableSchemaFilesDirectory;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\Resolver\Field\FieldResolverInterface;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class UniteMutationResolver implements FieldResolverInterface
{
    const TEST_QUERY = 'query { unite { _version } }';

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var SchemaManager $schemaManager
     */
    protected $schemaManager;

    public function __construct(DomainManager $domainManager, SchemaManager $schemaManager)
    {
        $this->domainManager = $domainManager;
        $this->schemaManager = $schemaManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteMutation';
    }

    /**
     * {@inheritDoc}
     * @throws \GraphQL\Error\Error
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        $domain = $this->domainManager->current();

        switch ($info->fieldName) {
            case 'updateSchemaFiles':

                if(empty($domain->getEditableSchemaFilesDirectory())) {
                    throw new NoEditableSchemaFilesDirectory();
                }

                // Create updated domain so we can try to build a schema with it
                $editableSchemaFiles = [];
                foreach($args['schemaFiles'] as $schemaFile) {
                    $editableSchemaFiles[$domain->getEditableSchemaFilesDirectory() . $schemaFile['name']] = $schemaFile['value'];
                }
                $updatedDomain = new Domain(
                    $domain->getId() . '_evaluate_' . uniqid(),
                    $domain->getContentManager(),
                    $domain->getUserManager(),
                    $domain->getLogger(),
                    array_merge($domain->getSchema(), $editableSchemaFiles)
                );

                // Executing a query against the schema will build and evaluate the complete schema.
                try {
                    $this->domainManager->setCurrentDomain($updatedDomain);
                    $result = $this->schemaManager->execute(self::TEST_QUERY, [], null, true);
                    $resultSuccess = !empty($result->data['unite']['_version']);

                // We don't catch errors here, so the client will see the errors, but we need to reset domain and schema.
                } finally {
                    $this->domainManager->setCurrentDomain($domain);
                    $this->schemaManager->execute(self::TEST_QUERY, [], null, true);
                }

                if($args['persist']) {
                    $fileSystem = new Filesystem();

                    // Create and update schema files.
                    foreach($editableSchemaFiles as $file => $value) {
                        $fileSystem->dumpFile($file, $value);
                    }

                    // Delete schema files.
                    array_keys($editableSchemaFiles);
                    foreach(DomainManager::findSchemaFilesInDir($domain->getEditableSchemaFilesDirectory()) as $file => $value) {
                        if(!array_key_exists($file, $editableSchemaFiles)) {
                            $fileSystem->remove($file);
                        }
                    }
                }

                return $resultSuccess;

            default: return null;
        }
    }
}
