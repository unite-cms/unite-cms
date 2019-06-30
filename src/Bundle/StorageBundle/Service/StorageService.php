<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 09:35
 */

namespace UniteCMS\StorageBundle\Service;

use Aws\S3\S3Client;
use Ramsey\Uuid\Uuid;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\NestableFieldTypeInterface;
use UniteCMS\StorageBundle\Field\Types\FileFieldType;
use UniteCMS\StorageBundle\Field\Types\ImageFieldType;
use UniteCMS\StorageBundle\Model\PreSignedUrl;

class StorageService
{

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * Resolves a nestable file field by a given path. At the moment, nestable
     * fields are only used for the collection field type.
     *
     * @param Fieldable $fieldable
     * @param $path
     *
     * @return null|FieldableField
     */
    public function resolveFileFieldPath(Fieldable $fieldable, $path)
    {
        /**
         * @var FieldableField $field
         */
        if (!$field = $fieldable->resolveIdentifierPath($path, true)) {
            return null;
        }

        // If we found an image field, we can return it
        if ($field->getType() == FileFieldType::TYPE || $field->getType() == ImageFieldType::TYPE) {
            return $field;
        } else {

            // If this field is nestable, continue resolving.
            $nestedFieldType = $this->fieldTypeManager->getFieldType($field->getType());
            if ($nestedFieldType instanceof NestableFieldTypeInterface) {
                return $this->resolveFileFieldPath($nestedFieldType::getNestableFieldable($field), $path);
            }
        }

        return null;
    }

    /**
     * Creates a PreSigned url object from the given command, using bucket settings, uuid and filename.
     *
     * @param string $command
     * @param array $bucket_settings
     * @param string $uuid
     * @param string $filename
     * @param string $expires
     * @return PreSignedUrl
     */
    protected function generatePreSignedUrl(string $command, array $bucket_settings, string $uuid, string $filename, string $expires = '+5 minutes') {

        // Return pre-signed url
        $s3Config = [
            'version' => 'latest',
            'region' => $bucket_settings['region'] ?? 'us-east-1',
            'endpoint' => $bucket_settings['endpoint'],
            'use_path_style_endpoint' => true,
        ];
        // The key and secret are optional - if they're not specified, the S3 client
        // object will attempt to fetch them from the EC2 instance metadata server.
        if (isset($bucket_settings['key']) && isset($bucket_settings['secret'])) {
            $s3Config['credentials'] = [
                'key' => $bucket_settings['key'],
                'secret' => $bucket_settings['secret'],
            ];
        }

        $s3Client = new S3Client($s3Config);

        // Set the upload file path to an optional path + uuid + filename.
        $filePath = $uuid.'/'.$filename;

        if (!empty($bucket_settings['path'])) {
            $path = trim($bucket_settings['path'], "/ \t\n\r\0\x0B");

            if (!empty($path)) {
                $filePath = $path.'/'.$filePath;
            }
        }

        $putObjectParams = [
            'Bucket' => $bucket_settings['bucket'],
            'Key' => $filePath,
        ];
        if (isset($bucket_settings['acl'])) {
            $putObjectParams['ACL'] = $bucket_settings['acl'];
        }
        $commandObj = $s3Client->getCommand($command, $putObjectParams);

        return new PreSignedUrl(
            (string)$s3Client->createPresignedRequest($commandObj, $expires)
                ->getUri(),
            $uuid,
            $filename
        );
    }

    /**
     * Pre-Signs an upload action for the given filename and field
     * configuration.
     *
     * @param string $filename
     * @param array $bucket_settings
     * @param string $allowed_file_types
     *
     * @param string $expires
     * @return PreSignedUrl
     * @throws \Exception
     */
    public function createPreSignedUploadUrl(string $filename, array $bucket_settings, string $allowed_file_types = '*', string $expires = '+5 minutes')
    {

        // Check if file type is allowed.
        $filenameparts = explode('.', $filename);
        if (count($filenameparts) < 2) {
            throw new \InvalidArgumentException(
                'Filename must include a file type extension.'
            );
        }

        $filenameextension = array_pop($filenameparts);
        $filenameextension_supported = false;

        foreach (explode(
                     ',',
                     str_replace(' ', '', $allowed_file_types)
                 ) as $extension) {
            if ($extension === '*') {
                $filenameextension_supported = true;
            }
            if ($filenameextension === strtolower($extension)) {
                $filenameextension_supported = true;
            }
        }

        if (!$filenameextension_supported) {
            throw new \InvalidArgumentException(
                'File type "'.$filenameextension.'" not supported'
            );
        }

        return $this->generatePreSignedUrl('PutObject', $bucket_settings, (string)Uuid::uuid1(), $filename, $expires);
    }

    /**
     * Pre-Signs an get-object action for the given filename and field configuration.
     *
     * @param string $uuid
     * @param string $filename
     * @param array $bucket_settings
     * @param string $expires
     *
     * @return PreSignedUrl
     * @throws \Exception
     */
    public function createPreSignedDownloadUrl(string $uuid, string $filename, array $bucket_settings, string $expires = '+5 minutes')
    {
        return $this->generatePreSignedUrl('GetObject', $bucket_settings, $uuid, $filename, $expires);
    }

    /**
     * Wrapper around createPreSignedUploadUrl to get settings from field
     * settings.
     *
     * @param string $filename
     * @param Fieldable $fieldable
     * @param string $field_path
     *
     * @return PreSignedUrl
     * @throws \Exception
     */
    public function createPreSignedUploadUrlForFieldPath(string $filename, Fieldable $fieldable, string $field_path)
    {

        if (!$field = $this->resolveFileFieldPath($fieldable, $field_path)) {
            throw new \InvalidArgumentException(
                'Field "'.$field_path.'" not found in fieldable.'
            );
        }

        // Check if config is available.
        if (!property_exists(
                $field->getSettings(),
                'bucket'
            ) || empty($field->getSettings()->bucket)) {
            throw new \InvalidArgumentException('Invalid field definition.');
        }

        // Check if config is available.
        $allowed_field_types = '*';
        if (property_exists(
                $field->getSettings(),
                'file_types'
            ) && !empty($field->getSettings()->file_types)) {
            $allowed_field_types = $field->getSettings()->file_types;
        }

        return $this->createPreSignedUploadUrl(
            $filename,
            $field->getSettings()->bucket,
            $allowed_field_types
        );
    }

    /**
     * Delete on object from s3 storage.
     *
     * @param string $uuid
     * @param string $filename
     * @param array $bucket_settings
     *
     * @return \Aws\Result
     */
    public function deleteObject(string $uuid, string $filename, array $bucket_settings)
    {
        $s3Config = [
            'version' => 'latest',
            'region' => $bucket_settings['region'] ?? 'us-east-1',
            'endpoint' => $bucket_settings['endpoint'],
            'use_path_style_endpoint' => true
        ];
        // The key and secret are optional - if they're not specified, the S3 client
        // object will attempt to fetch them from the EC2 instance metadata server.
        if (isset($bucket_settings['key']) && isset($bucket_settings['secret'])) {
            $s3Config['credentials'] = [
                'key' => $bucket_settings['key'],
                'secret' => $bucket_settings['secret'],
            ];
        }

        $s3Client = new S3Client($s3Config);

        // Set the upload file path to an optional path + uuid + filename.
        $filePath = $uuid.'/'.$filename;

        if (!empty($bucket_settings['path'])) {
            $path = trim($bucket_settings['path'], "/ \t\n\r\0\x0B");

            if (!empty($path)) {
                $filePath = $path.'/'.$filePath;
            }
        }

        return $s3Client->deleteObject(
            [
                'Bucket' => $bucket_settings['bucket'],
                'Key' => $filePath,
            ]
        );
    }
}
