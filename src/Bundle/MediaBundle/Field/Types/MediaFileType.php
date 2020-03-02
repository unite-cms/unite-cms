<?php

namespace UniteCMS\MediaBundle\Field\Types;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\Type;
use League\Flysystem\FileNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\Types\AbstractFieldType;
use UniteCMS\MediaBundle\Flysystem\FlySystemManager;
use UniteCMS\MediaBundle\Flysystem\UploadToken;

class MediaFileType extends AbstractFieldType
{
    const TYPE = 'mediaFile';
    const GRAPHQL_INPUT_TYPE = Type::STRING;

    /**
     * @var JWTEncoderInterface $JWTEncoder
     */
    protected $JWTEncoder;

    /**
     * @var FlySystemManager $flySystemManager
     */
    protected $flySystemManager;

    public function __construct(SaveExpressionLanguage $expressionLanguage, JWTEncoderInterface $JWTEncoder, FlySystemManager $flySystemManager)
    {
        $this->JWTEncoder = $JWTEncoder;
        $this->flySystemManager = $flySystemManager;
        parent::__construct($expressionLanguage);
    }

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UniteMediaFile'];
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Field/' . static::getType() . '.graphql');
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldData(ContentInterface $content, ContentTypeField $field, ContextualValidatorInterface $validator, ExecutionContextInterface $context, FieldData $fieldData = null) : void {
        parent::validateFieldData($content, $field, $validator, $context, $fieldData);

        if($validator->getViolations()->count() > 0) {
            return;
        }

        $fieldDataRows = $fieldData instanceof FieldDataList ? $fieldData->rows() : [$fieldData];

        foreach($fieldDataRows as $row) {
            if($row && !$row->empty()) {
                $validator->validate($row->resolveData('type'), new EqualTo($field->getType()), [$context->getGroup()]);
                $validator->validate($row->resolveData('field'), new EqualTo($field->getId()), [$context->getGroup()]);

                if($maxSize = $field->getSettings()->get('maxFilesize')) {
                    $validator->validate($row->resolveData('filesize'), new LessThanOrEqual($maxSize * 1000 * 1000), [$context->getGroup()]);
                }

                if($allowedMimtypes = $field->getSettings()->get('allowedMimetypes')) {
                    $pattern = '/' . str_replace('/', '\/', join('|', $allowedMimtypes)) . '/i';

                    $validator->validate($row->resolveData('mimetype'), new Regex([
                        'pattern' => $pattern,
                    ]), [$context->getGroup()]);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {
        $settings = parent::getPublicSettings($field) ?? new ArrayCollection();
        $settings->set('maxFilesize', $field->getSettings()->get('maxFilesize'));
        $settings->set('allowedMimetypes', $field->getSettings()->get('allowedMimetypes'));

        if($field->getSettings()->get('s3')) {
            if(!empty($field->getSettings()->get('s3')['ACL'])) {
                $settings->set('requestHeaders', [
                    'x-amz-acl' => $field->getSettings()->get('s3')['ACL'],
                ]);
            }
        }

        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null) : FieldData {

        // If empty input data, we return an empty field data object.
        if(empty($inputData)) {
            return new FieldData();
        }

        // If input data is an existing file id, we can use this
        $fieldData = $content->getFieldData($field->getId());
        if($fieldData && !$fieldData->empty()) {

            $fieldDataRows = $fieldData instanceof FieldDataList ? $fieldData->rows() : [$fieldData];

            foreach ($fieldDataRows as $fieldDataRow) {
                if ($fieldDataRow->resolveData('id') === $inputData) {
                    return $fieldDataRow;
                }
            }
        }

        try {
            $uploadToken = UploadToken::fromArray(
                $this->JWTEncoder->decode($inputData)
            );
        } catch (JWTDecodeFailureException $e) {
            throw new UserError(sprintf('Could not decode JWT token because of %s', $e->getReason()));
        }

        // Load file information from tmp file.
        $config = $field->getSettings()->get($uploadToken->getDriver());
        $flySystem = $this->flySystemManager->createFilesystem($uploadToken->getDriver(), $config);

        try {
            $fileSize = $flySystem->getSize($config['tmp_path'].'/'.$uploadToken->getId().'/'.$uploadToken->getFilename());
            $mimeType = $flySystem->getMimetype($config['tmp_path'].'/'.$uploadToken->getId().'/'.$uploadToken->getFilename());
        } catch (FileNotFoundException $e) {
            throw new UserError('Could not save file information, because tmp file was not found.');
        } catch (Exception $e) {
            throw new UserError('Could not save file information, because fileserver is not reachable.');
        }

        return new FieldData([
            'id' => $uploadToken->getId(),
            'filename' => $uploadToken->getFilename(),
            'driver' => $uploadToken->getDriver(),
            'filesize' => $fileSize,
            'mimetype' => $mimeType,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {

        if($fieldData->empty()) {
            return null;
        }

        return [
            'id' => $fieldData->resolveData('id'),
            'filename' => $fieldData->resolveData('filename'),
            'driver' => $fieldData->resolveData('driver'),
            'filesize' => $fieldData->resolveData('filesize'),
            'mimetype' => $fieldData->resolveData('mimetype'),
            'config' => $field->getSettings()->get($fieldData->resolveData('driver')),
        ];
    }
}
