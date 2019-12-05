<?php


namespace UniteCMS\MediaBundle\EventSubscriber;

use GraphQL\Error\UserError;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedContent;
use UniteCMS\CoreBundle\Content\Embedded\EmbeddedFieldData;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Field\Types\EmbeddedType;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\MediaBundle\Field\Types\MediaFileType;
use UniteCMS\MediaBundle\Flysystem\FlySystemManager;

class ContentFileSubscriber implements EventSubscriberInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var FlySystemManager $flySystemManager
     */
    protected $flySystemManager;

    public function __construct(DomainManager $domainManager, FlySystemManager $flySystemManager)
    {
        $this->domainManager = $domainManager;
        $this->flySystemManager = $flySystemManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContentEventAfter::CREATE => 'onCreate',
            ContentEventAfter::UPDATE => 'onUpdate',
            ContentEventAfter::REVERT => 'onRevert',
            ContentEventAfter::PERMANENT_DELETE => 'onPermanentDelete',
        ];
    }

    /**
     * @param ContentInterface $content
     * @return ContentTypeField[]
     */
    protected function getFileFields(ContentInterface $content) : array {
        $fileFields = [];
        $contentType = $this->domainManager->current()->getContentTypeManager()->getAnyType($content->getType());
        foreach($contentType->getFields() as $field) {
            if($field->getType() === MediaFileType::getType()) {
                $fileFields[] = $field;
            }

            if($field->getType() === EmbeddedType::getType()) {
                if($content->getFieldData($field->getId()) instanceof EmbeddedFieldData) {
                    $fileFields = array_merge($fileFields, $this->getFileFields(new EmbeddedContent($content->getFieldData($field->getId())->getId(), $content->getFieldData($field->getId())->getType(), $content->getFieldData($field->getId())->getData())));
                }
            }
        }

        return $fileFields;
    }

    /**
     * @param ContentInterface $content
     * @param ContentTypeField $field
     * @param FieldData $fieldData
     */
    protected function uploadFile(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {

        $filename = $fieldData->resolveData('filename');
        $driver = $fieldData->resolveData('driver');
        $config = $field->getSettings()->get($driver);

        $this->domainManager->current()->log(LoggerInterface::NOTICE, sprintf(
            'Start to upload file %s for field "%s" with driver "%s" of type %s...',
            $filename,
            $field->getId(),
            $driver,
            $content->getType()
        ));

        $flySystem = $this->flySystemManager->createFilesystem($driver, $config);

        try {
            $flySystem->rename(
                $config['tmp_path'] . '/' . $fieldData->resolveData('id') . '/' . $filename,
                $config['path'] . '/' . $fieldData->resolveData('id') . '/' . $filename
            );
        } catch (FileExistsException $e) {
            $this->domainManager->current()->log(LoggerInterface::ERROR, sprintf('Could not upload file %s because a file already exists at destination path. Please try again!', $filename));
        } catch (FileNotFoundException $e) {
            $this->domainManager->current()->log(LoggerInterface::ERROR, sprintf('Could not upload file %s because the source file does not exist. Please upload the file again!', $filename));
        } catch (\Exception $e) {
            $this->domainManager->current()->log(LoggerInterface::ERROR, sprintf('Could not upload file %s because fileserver is not reachable!', $filename));
        }
    }

    /**
     * @param ContentInterface $content
     * @param ContentTypeField $field
     * @param FieldData $fieldData
     */
    protected function deleteFile(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {

        $filename = $fieldData->resolveData('filename');
        $driver = $fieldData->resolveData('driver');
        $config = $field->getSettings()->get($driver);

        $this->domainManager->current()->log(LoggerInterface::NOTICE, sprintf(
            'Start to delete file %s for field %s with driver "%s" of type %s.',
            $filename,
            $field->getId(),
            $driver,
            $content->getType()
        ));

        $flySystem = $this->flySystemManager->createFilesystem($driver, $config);

        try {
            $flySystem->delete($config['path'] . '/' . $fieldData->resolveData('id') . '/' . $filename);
        } catch (FileNotFoundException $e) {
            $this->domainManager->current()->log(LoggerInterface::ERROR, sprintf('Could not delete file %s because the file does not exist.', $filename));
        } catch (\Exception $e) {
            $this->domainManager->current()->log(LoggerInterface::ERROR, sprintf('Could not delete file %s because fileserver is not reachable!', $filename));
        }
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onCreate(ContentEvent $event) {
        $content = $event->getContent();
        foreach($this->getFileFields($content) as $fileField) {
            if($content->getFieldData($fileField->getId()) && !$content->getFieldData($fileField->getId())->empty()) {
                $this->uploadFile($content, $fileField, $content->getFieldData($fileField->getId()));
            }
        }
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onUpdate(ContentEvent $event) {
        $content = $event->getContent();

        foreach($this->getFileFields($content) as $fileField) {
            if($content->getFieldData($fileField->getId()) && !$content->getFieldData($fileField->getId())->empty()) {

                // TODO: Should be remove old files on update?
                // $prevData = $event->getPreviousData();
                //if(!empty($prevData[$fileField->getId()])) {
                //    $this->deleteFile($content, $fileField, $prevData[$fileField->getId()]);
                //}

                // Add new file on update.
                $this->uploadFile($content, $fileField, $content->getFieldData($fileField->getId()));
            }
        }
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onRevert(ContentEvent $event) {
        $content = $event->getContent();

        foreach($this->getFileFields($content) as $fileField) {
            if($content->getFieldData($fileField->getId()) && !$content->getFieldData($fileField->getId())->empty()) {

                // TODO: Should be remove old files on revert?
                // $prevData = $event->getPreviousData();
                //if(!empty($prevData[$fileField->getId()])) {
                //    $this->deleteFile($content, $fileField, $prevData[$fileField->getId()]);
                //}

                // Add new file on update.
                $this->uploadFile($content, $fileField, $content->getFieldData($fileField->getId()));
            }
        }
    }

    /**
     * @param \UniteCMS\CoreBundle\Event\ContentEvent $event
     */
    public function onPermanentDelete(ContentEvent $event) {
        $content = $event->getContent();
        foreach($this->getFileFields($content) as $fileField) {
            if($content->getFieldData($fileField->getId()) && !$content->getFieldData($fileField->getId())->empty()) {
                $this->deleteFile($content, $fileField, $content->getFieldData($fileField->getId()));
            }
        }
    }
}
