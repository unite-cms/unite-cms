<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 13.02.18
 * Time: 11:37
 */

namespace UnitedCMS\StorageBundle\Command;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\Entity\SettingType;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Field\NestableFieldTypeInterface;
use UnitedCMS\StorageBundle\Field\Types\FileFieldType;
use UnitedCMS\StorageBundle\Field\Types\ImageFieldType;

class StorageFileDeleteUnused extends Command
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, FieldTypeManager $fieldTypeManager)
    {
        $this->em = $em;
        $this->fieldTypeManager = $fieldTypeManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('united:storage:delete-unused')
          ->setDescription('Deletes all files from a given s3 bucket, that are not referenced by a file field.')
          ->addOption('force', InputOption::VALUE_OPTIONAL);
    }

    private function findNestedFieldData(array $data, array $path_parts) {
        $file_usage = [];
        $root_part = array_shift($path_parts);

        if(!empty($data[$root_part])) {

            // If this was the last element in the path, try to find content.
            if(empty($path_parts)) {
                $file_usage[] = $data[$root_part]['id'] . '/' . $data[$root_part]['name'];
            }

            // If we can find nested data.
            else {
                foreach($data[$root_part] as $child) {
                    $file_usage = array_merge($file_usage, $this->findNestedFieldData($child, $path_parts));
                }
            }
        }

        return $file_usage;
    }

    private function findNestedFileDefinitions(FieldableField $field, &$buckets) {



        $fieldPathParts = explode('/', $field->getEntity()->getIdentifierPath());
        array_shift($fieldPathParts);
        $fieldPathParts[] = $field->getIdentifier();

        // Handle file fields.
        if($field->getType() == FileFieldType::TYPE || $field->getType() == ImageFieldType::TYPE) {
            $bucket_path = $field->getSettings()->bucket['endpoint'] . '/' . $field->getSettings()->bucket['bucket'];

            // Create basic bucket information.
            if(!isset($buckets[$bucket_path])) {
                $buckets[$bucket_path] = [
                  'endpoint' => $field->getSettings()->bucket['endpoint'],
                  'bucket' => $field->getSettings()->bucket['bucket'],
                  'credentials' => [
                    'key' => $field->getSettings()->bucket['key'],
                    'secret' => $field->getSettings()->bucket['secret'],
                  ],
                  'files' => [],
                ];
            }

            // Find usage for (possible nested) content.
            if($field->getEntity()->getRootEntity() instanceof ContentType) {
                foreach ($this->em->getRepository('UnitedCMSCoreBundle:Content')->findBy(['contentType' => $field->getEntity()->getRootEntity()]) as $content) {
                    $buckets[$bucket_path]['files'] = array_merge($buckets[$bucket_path]['files'], $this->findNestedFieldData($content->getData(), $fieldPathParts));
                }
            }

            // Find usage for (possible nested) setting.
            if($field->getEntity()->getRootEntity() instanceof SettingType) {
                foreach ($this->em->getRepository('UnitedCMSCoreBundle:Setting')->findBy(['settingType' => $field->getEntity()->getRootEntity()]) as $setting) {
                    $buckets[$bucket_path]['files'] = array_merge($buckets[$bucket_path]['files'], $this->findNestedFieldData($setting->getData(), $fieldPathParts));
                }
            }
        }

        else {
            // If this field is nestable, continue resolving.
            $nestedFieldType = $this->fieldTypeManager->getFieldType($field->getType());
            if($nestedFieldType instanceof NestableFieldTypeInterface) {
                foreach($nestedFieldType::getNestableFieldable($field)->getFields() as $field) {
                    $this->findNestedFileDefinitions($field, $buckets);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $buckets = [];

        $this->em->getFilters()->disable('gedmo_softdeleteable');

        // Find all content file fields.
        foreach($this->em->getRepository('UnitedCMSCoreBundle:ContentTypeField')->findBy(['type' => [FileFieldType::TYPE, ImageFieldType::TYPE, 'collection']]) as $field) {
            $this->findNestedFileDefinitions($field, $buckets);
        }

        // Find all setting file fields.
        foreach($this->em->getRepository('UnitedCMSCoreBundle:SettingTypeField')->findBy(['type' => [FileFieldType::TYPE, ImageFieldType::TYPE, 'collection']]) as $field) {
            $this->findNestedFileDefinitions($field, $buckets);
        }

        $this->em->getFilters()->enable('gedmo_softdeleteable');


        $output->writeln(['', '', '', 'The following files are in use:']);

        foreach($buckets as $bucket => $config) {
            $output->writeln(['','<bg=green;fg=white>' . $bucket . '</>']);

            foreach($config['files'] as $file) {
                $output->writeln(['     ' . $file]);
            }
        }

        $output->writeln(['', '', '', 'The following files are not in use and will be deleted if you pass --force:']);

        foreach($buckets as $bucket => $config) {
            $output->writeln(['','<bg=red;fg=white>' . $bucket . '</>']);

            $objects_to_delete = [];

            // Return pre-signed url
            $s3Client = new S3Client([
              'version' => 'latest',
              'region'  => 'us-east-1',
              'endpoint' => $config['endpoint'],
              'use_path_style_endpoint' => true,
              'credentials' => [
                'key'    => $config['credentials']['key'],
                'secret' => $config['credentials']['secret'],
              ],
            ]);

            foreach($s3Client->getIterator('ListObjects', array('Bucket' => $config['bucket'])) as $object) {
                if(!in_array($object['Key'], $config['files'])) {
                    $objects_to_delete[] = ['Key' => $object['Key']];
                    $output->writeln(['     ' . $object['Key']]);
                }
            }

            if($input->getOption('force')) {

                $s3Client->deleteObjects([
                    'Bucket'  => $config['bucket'],
                    'Delete' => [
                        'Objects' => $objects_to_delete,
                    ],
                ]);

                $output->writeln([
                  '',
                  '',
                  '<bg=black;fg=white;options=bold>*****************************************</>',
                  '<bg=black;fg=white;options=bold>         ' . count($objects_to_delete) . ' files where deleted.         </>',
                  '<bg=black;fg=white;options=bold>*****************************************</>'
                ]);
            }
        }

        $output->writeln(['']);
    }
}