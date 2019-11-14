<?php


namespace UniteCMS\AdminBundle\EditableSchemaFiles;


use Symfony\Component\Filesystem\Filesystem;
use UniteCMS\AdminBundle\AdminView\AdminView;
use UniteCMS\AdminBundle\Exception\NoEditableSchemaFilesDirectory;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class EditableSchemaFileManager
{
    const TEST_QUERY = 'query { unite { _version } }';

    /**
     * @var \UniteCMS\CoreBundle\Domain\DomainManager
     */
    protected $domainManager;

    /**
     * @var \UniteCMS\CoreBundle\GraphQL\SchemaManager
     */
    protected $schemaManager;

    public function __construct(DomainManager $domainManager, SchemaManager $schemaManager)
    {
        $this->domainManager = $domainManager;
        $this->schemaManager = $schemaManager;
    }

    /**
     * @param Domain $domain
     * @return AdminView[]
     */
    public function getEditableSchemaFiles(Domain $domain = null) : array {

        if(!$domain) {
            $domain = $this->domainManager->current();
        }

        if(empty($domain->getEditableSchemaFilesDirectory())) {
            throw new NoEditableSchemaFilesDirectory();
        }

        $domainSchemaFiles = [];
        foreach(DomainManager::findSchemaFilesInDir($domain->getEditableSchemaFilesDirectory()) as $name => $value) {
            $nameParts = explode('/', $name);
            $domainSchemaFiles[] = [
                'name' => array_pop($nameParts),
                'value' => $value,
            ];
        }
        return $domainSchemaFiles;
    }

    /**
     * @param \UniteCMS\CoreBundle\Domain\Domain|null $domain
     * @param array $schemaFiles
     * @param bool $persist
     *
     * @return bool
     * @throws \GraphQL\Error\Error
     * @throws \GraphQL\Error\SyntaxError
     */
    public function updateEditableSchemaFiles(Domain $domain = null, array $schemaFiles = [], bool $persist = false) {

        if(!$domain) {
            $domain = $this->domainManager->current();
        }

        if(empty($domain->getEditableSchemaFilesDirectory())) {
            throw new NoEditableSchemaFilesDirectory();
        }

        // Create updated domain so we can try to build a schema with it
        $editableSchemaFiles = [];
        foreach($schemaFiles as $schemaFile) {
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

        if($persist) {
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
    }
}
