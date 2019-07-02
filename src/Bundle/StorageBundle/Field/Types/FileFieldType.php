<?php

namespace UniteCMS\StorageBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\StorageBundle\Form\StorageFileType;
use UniteCMS\StorageBundle\Model\PreSignedUrl;
use UniteCMS\StorageBundle\Service\StorageService;

class FileFieldType extends FieldType
{
    const TYPE                      = "file";
    const FORM_TYPE                 = StorageFileType::class;
    const SETTINGS                  = ['not_empty', 'description', 'file_types', 'bucket', 'form_group'];
    const REQUIRED_SETTINGS         = ['bucket'];

    protected $router;
    protected $secret;
    protected $storageService;
    protected $csrfTokenManager;
    protected $translator;

    public function __construct(Router $router, string $secret, StorageService $storageService, CsrfTokenManager $csrfTokenManager, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->secret = $secret;
        $this->storageService = $storageService;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->translator = $translator;
    }

    /**
     * Returns the bucket endpoint, including an optional sub-path.
     *
     * @param FieldableFieldSettings $settings
     * @return string
     */
    protected function generateEndpoint(FieldableFieldSettings $settings) : string {
        $endpoint = $settings->bucket['endpoint'].'/'.$settings->bucket['bucket'];

        if (!empty($settings->bucket['path'])) {
            $path = trim($settings->bucket['path'], "/ \t\n\r\0\x0B");

            if (!empty($path)) {
                $endpoint = $endpoint.'/'.$path;
            }
        }

        return $endpoint;
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
              'organization' => IdentifierNormalizer::denormalize($fieldable->getDomain()->getOrganization()->getIdentifier()),
              'domain' => IdentifierNormalizer::denormalize($fieldable->getDomain()->getIdentifier()),
              'content_type' => IdentifierNormalizer::denormalize($fieldable->getIdentifier()),
            ], Router::ABSOLUTE_URL);
        }

        else if($fieldable instanceof SettingType) {
            $url = $this->router->generate('unitecms_storage_sign_uploadsettingtype', [
              'organization' => IdentifierNormalizer::denormalize($fieldable->getDomain()->getOrganization()->getIdentifier()),
              'domain' => IdentifierNormalizer::denormalize($fieldable->getDomain()->getIdentifier()),
              'setting_type' => IdentifierNormalizer::denormalize($fieldable->getIdentifier()),
            ], Router::ABSOLUTE_URL);
        }

        return array_merge(parent::getFormOptions($field), [
          'attr' => [
            'file-types' => $field->getSettings()->file_types,
            'field-path' => $field->getIdentifierPath('/', false),
            'endpoint' => $this->generateEndpoint($field->getSettings()),
            'upload-sign-url' => $url,
            'upload-sign-csrf-token' => $this->csrfTokenManager->getToken('pre_sign_form'),
            'acl' => $field->getSettings()->bucket['acl'] ?? '',
            'messages' => json_encode([
                'select' => $this->translator->trans('storage.field.select.message'),
                'select_one' => $this->translator->trans('storage.field.select.message.one'),
                'select_multiple' => $this->translator->trans('storage.field.select.message.multiple'),
                'error_delete_first' => $this->translator->trans('storage.field.error.error_delete_first'),
                'error_sign' => $this->translator->trans('storage.field.error.sign'),
                'confirm_delete' => $this->translator->trans('storage.field.confirm.delete'),
            ]),
          ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager) {
        return $schemaTypeManager->getSchemaType('StorageFile');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager) {
        return $schemaTypeManager->getSchemaType('StorageFileInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        if(empty($value['id']) || empty($value['name'])) {
            return null;
        }

        // Create full URL to file.
        $value['url'] = $this->generateEndpoint($field->getSettings()) . '/' . $value['id'] . '/' . $value['name'];

        // Create pre_signed URL.
        $value['presigned_url'] = $this->storageService->createPreSignedDownloadUrl(
            $value['id'],
            $value['name'],
            $field->getSettings()->bucket,
            $field->getSettings()->bucket['pre_signed_download_expiration'] ?? '+1 hours'
        )->getPreSignedUrl();

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    function validateData(FieldableField $field, $data, ExecutionContextInterface $context)
    {
        // When deleting content, we don't need to validate data.
        if(strtoupper($context->getGroup()) === 'DELETE') {
            return;
        }

        if(empty($data)) {
            return;
        }

        if(empty($data['size']) || empty($data['id']) || empty($data['name']) || empty($data['checksum'])) {
            $context->buildViolation('storage.missing_file_definition')->atPath('['.$field->getIdentifier().']')->addViolation();
            return;
        }

        if(empty($violations)) {
            $preSignedUrl = new PreSignedUrl('', $data['id'], $data['name'], $data['checksum']);
            if (!$preSignedUrl->check($this->secret)) {
                $context->buildViolation('storage.invalid_checksum')->atPath('['.$field->getIdentifier().']')->addViolation();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Validate allowed bucket configuration.
        if($context->getViolations()->count() == 0) {
            foreach($settings->bucket as $field => $value) {
                if(!in_array($field, ['endpoint', 'key', 'secret', 'bucket', 'path', 'region', 'acl', 'pre_signed_download_expiration'])) {
                    $context->buildViolation('additional_data')->atPath('bucket.' . $field)->addViolation();
                }
            }
        }

        // Validate required bucket configuration.
        if($context->getViolations()->count() == 0) {
            foreach(['endpoint', 'bucket'] as $required_field) {
                if(!isset($settings->bucket[$required_field])) {
                    $context->buildViolation('required')->atPath('bucket.' . $required_field)->addViolation();
                }
            }
        }

        if($context->getViolations()->count() == 0) {
            if(!preg_match("/^(http|https):\/\//", $settings->bucket['endpoint'])) {
                $context->buildViolation('storage.absolute_url')->atPath('bucket.endpoint')->addViolation();
            }
        }

        // validate for trailing slash
        if($context->getViolations()->count() == 0) {
            // if this does not match, there is a trainling slash
            if (rtrim($settings->bucket['endpoint'], '/\\') != $settings->bucket['endpoint'])
            {
                $context->buildViolation('storage.no_trailing_slash')->atPath('bucket.endpoint')->addViolation();
            }
        }

        // validate expiration time
        if($context->getViolations()->count() == 0) {
            if(!empty($settings->bucket['pre_signed_download_expiration'])) {
                if(strtotime($settings->bucket['pre_signed_download_expiration']) === false) {
                    $context->buildViolation('storage.invalid_expiration_time')->atPath('bucket.pre_signed_download_expiration')->addViolation();
                }
                else if(strtotime($settings->bucket['pre_signed_download_expiration']) > strtotime('+1 weeks')) {
                    $context->buildViolation('storage.invalid_expiration_time')->atPath('bucket.pre_signed_download_expiration')->addViolation();
                }
            }
        }
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
     * @param FieldableContent $content
     * @param EntityRepository $repository
     * @param $data
     */
    public function onHardDelete(FieldableField $field, FieldableContent $content, EntityRepository $repository, $data) {
        if(isset($data[$field->getIdentifier()])) {

            // If we hard delete this content, delete the attached file.
            $file = $data[$field->getIdentifier()];
            $this->storageService->deleteObject($file['id'], $file['name'], $field->getSettings()->bucket);
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterViewFieldSettings(array &$settings, FieldTypeManager $fieldTypeManager, FieldableField $field = null) {
        parent::alterViewFieldSettings($settings, $fieldTypeManager, $field);
        $settings['assets'] = [
            ['js' => 'main.js', 'package' => 'UniteCMSStorageBundle'],
            ['css' => 'main.css', 'package' => 'UniteCMSStorageBundle'],
        ];
    }
}
