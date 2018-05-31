<?php

namespace UniteCMS\StorageBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\ConstraintViolation;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\StorageBundle\Form\StorageFileType;
use UniteCMS\StorageBundle\Model\PreSignedUrl;
use UniteCMS\StorageBundle\Service\StorageService;

class FileFieldType extends FieldType
{
    const TYPE                      = "file";
    const FORM_TYPE                 = StorageFileType::class;
    const SETTINGS                  = ['file_types', 'bucket'];
    const REQUIRED_SETTINGS         = ['bucket'];

    private $router;
    private $secret;
    private $storageService;
    private $csrfTokenManager;

    public function __construct(Router $router, string $secret, StorageService $storageService, CsrfTokenManager $csrfTokenManager)
    {
        $this->router = $router;
        $this->secret = $secret;
        $this->storageService = $storageService;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        $url = null;

        // To generate the sing url we need to find out the base fieldable.
        $fieldable = $field->getEntity()->getRootEntity();

        if($fieldable instanceof ContentType) {
            $url = $this->router->generate('unitecms_storage_sign_uploadcontenttype', [
              'organization' => $fieldable->getDomain()->getOrganization()->getIdentifier(),
              'domain' => $fieldable->getDomain()->getIdentifier(),
              'content_type' => $fieldable->getIdentifier(),
            ], Router::ABSOLUTE_URL);
        }

        else if($fieldable instanceof SettingType) {
            $url = $this->router->generate('unitecms_storage_sign_uploadsettingtype', [
              'organization' => $fieldable->getDomain()->getOrganization()->getIdentifier(),
              'domain' => $fieldable->getDomain()->getIdentifier(),
              'content_type' => $fieldable->getIdentifier(),
            ], Router::ABSOLUTE_URL);
        }

        // Use the identifier path part, but exclude root entity and include field identifier.
        $identifier_path_parts = explode('/', $field->getEntity()->getIdentifierPath());
        array_shift($identifier_path_parts);
        $identifier_path_parts[] = $field->getIdentifier();


        $endpoint = $field->getSettings()->bucket['endpoint'].'/'.$field->getSettings()->bucket['bucket'];

        if (!empty($field->getSettings()->bucket['path'])) {
            $path = trim($field->getSettings()->bucket['path'], "/ \t\n\r\0\x0B");

            if (!empty($path)) {
                $endpoint = $endpoint.'/'.$path;
            }
        }

        return array_merge(parent::getFormOptions($field), [
          'attr' => [
            'file-types' => $field->getSettings()->file_types,
            'field-path' => join('/', $identifier_path_parts),
            'endpoint' => $endpoint,
            'upload-sign-url' => $url,
            'upload-sign-csrf-token' => $this->csrfTokenManager->getToken('pre_sign_form'),
          ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('StorageFile');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return $schemaTypeManager->getSchemaType('StorageFileInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value)
    {
        // Create full URL to file.
        $value['url'] = $field->getSettings()->bucket['endpoint'] . '/' . $field->getSettings()->bucket['bucket'] . '/' . $value['id'] . '/' . $value['name'];
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, $validation_group = 'DEFAULT'): array
    {
        // When deleting content, we don't need to validate data.
        if($validation_group === 'DELETE') {
            return [];
        }

        $violations = [];

        if(empty($data)) {
            return $violations;
        }

        if(empty($data['size']) || empty($data['id']) || empty($data['name']) || empty($data['checksum'])) {
            $violations[] = $this->createViolation($field, 'validation.missing_definition');
        }

        if(empty($violations)) {
            $preSignedUrl = new PreSignedUrl('', $data['id'], $data['name'], $data['checksum']);
            if (!$preSignedUrl->check($this->secret)) {
                $violations[] = $this->createViolation($field, 'storage.invalid_checksum');
            }
        }

        return $violations;
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        // Validate allowed and required settings.
        $violations = parent::validateSettings($field, $settings);

        // Validate allowed bucket configuration.
        if(empty($violations)) {
            foreach($settings->bucket as $field => $value) {
                if(!in_array($field, ['endpoint', 'key', 'secret', 'bucket', 'path', 'region'])) {
                    $violations[] = new ConstraintViolation(
                        'validation.additional_data',
                        'validation.additional_data',
                        [],
                        $settings->bucket,
                        'bucket.' . $field,
                        $settings->bucket
                    );
                }
            }
        }

        // Validate required bucket configuration.
        if(empty($violations)) {
            foreach(['endpoint', 'key', 'secret', 'bucket'] as $required_field) {
                if(!isset($settings->bucket[$required_field])) {
                    $violations[] = new ConstraintViolation(
                      'validation.required',
                      'validation.required',
                      [],
                      $settings->bucket,
                      'bucket.' . $required_field,
                      $settings->bucket
                    );
                }
            }
        }

        if(empty($violations)) {
            if(!preg_match("/^(http|https):\/\//", $settings->bucket['endpoint'])) {
                $violations[] = new ConstraintViolation(
                  'storage.absolute_url',
                  'storage.absolute_url',
                  [],
                  $settings->bucket,
                  'bucket.endpoint',
                  $settings->bucket
                );
            }
        }

        return $violations;
    }

    /**
     * On update delete old file from s3 bucket.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @param EntityRepository $repository
     * @param $old_data
     * @param $data
     */
    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {

        if(isset($old_data[$field->getIdentifier()])) {

            $old_file = $old_data[$field->getIdentifier()];
            $new_file = isset($data[$field->getIdentifier()]) ? $data[$field->getIdentifier()] : null;

            // the fields in the file array can be in any order, so we need to sort them for checking differences.
            asort($old_file);

            if(is_array($new_file)) {
                asort($new_file);
            }

            // If we have a new file, delete the old one.
            if($old_file != $new_file) {
                $this->storageService->deleteObject($old_file['id'], $old_file['name'], $field->getSettings()->bucket);
            }
        }
    }

    /**
     * On content hard delete, delete the file from s3 bucket.
     *
     * @param FieldableField $field
     * @param Content $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onHardDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {
        if(isset($data[$field->getIdentifier()])) {

            // If we hard delete this content, delete the attached file.
            $file = $data[$field->getIdentifier()];
            $this->storageService->deleteObject($file['id'], $file['name'], $field->getSettings()->bucket);
        }
    }
}
