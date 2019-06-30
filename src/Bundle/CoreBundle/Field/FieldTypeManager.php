<?php

namespace UniteCMS\CoreBundle\Field;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingTypeField;

class FieldTypeManager
{
    /**
     * @var FieldTypeInterface[]
     */
    private $fieldTypes = [];

    /**
     * @return FieldTypeInterface[]
     */
    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }

    public function hasFieldType($key): bool
    {
        return array_key_exists($key, $this->fieldTypes);
    }

    public function getFieldType($key): FieldTypeInterface
    {
        if (!$this->hasFieldType($key)) {
            throw new \InvalidArgumentException("The field type: '$key' was not found.");
        }

        return $this->fieldTypes[$key];
    }

    /**
     * Validates content data for given field by using the validation method of the field type.
     *
     * @param FieldableField $field
     * @param mixed $data
     * @param ExecutionContextInterface $context
     */
    public function validateFieldData(FieldableField $field, $data, ExecutionContextInterface $context)
    {
        $fieldType = $this->getFieldType($field->getType());
        $fieldType->validateData($field, $data, $context);
    }

    /**
     * Validates field settings for given field by using the validation method of the field type.
     *
     * @param FieldableFieldSettings $settings
     * @param ExecutionContextInterface $context
     */
    public function validateFieldSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        $fieldType = $this->getFieldType($context->getObject()->getType());
        $fieldType->validateSettings($settings, $context);
    }

    /**
     * Alter content data for given field.
     *
     * @param FieldableField $field
     * @param mixed $data
     * @param FieldableContent $content
     * @param array $rootData
     */
    public function alterFieldData(FieldableField $field, &$data, FieldableContent $content, $rootData)
    {
        $fieldType = $this->getFieldType($field->getType());
        $fieldType->alterData($field, $data, $content, $rootData);
    }

    public function onContentInsert(ContentTypeField $field, Content $content, LifecycleEventArgs $args)
    {
        $fieldType = $this->getFieldType($field->getType());
        if (method_exists($fieldType, 'onCreate')) {
            $data = $content->getData();
            $fieldType->onCreate(
                $field,
                $content,
                $args->getObjectManager()->getRepository('UniteCMSCoreBundle:Content'),
                $data
            );
            $content->setData($data);
        }
    }

    public function onContentUpdate(ContentTypeField $field, Content $content, PreUpdateEventArgs $args)
    {
        $fieldType = $this->getFieldType($field->getType());
        if (method_exists($fieldType, 'onUpdate')) {
            $data = $content->getData();
            $old_data = $args->hasChangedField('data') ? $args->getOldValue('data') : [];
            $fieldType->onUpdate(
                $field,
                $content,
                $args->getObjectManager()->getRepository('UniteCMSCoreBundle:Content'),
                $old_data,
                $data
            );
            $content->setData($data);
        }
    }

    public function onSettingInsert(SettingTypeField $field, Setting $setting, LifecycleEventArgs $args)
    {
        $fieldType = $this->getFieldType($field->getType());
        if (method_exists($fieldType, 'onUpdate')) {
            $data = $setting->getData();
            $fieldType->onUpdate(
                $field,
                $setting,
                $args->getObjectManager()->getRepository('UniteCMSCoreBundle:Setting'),
                [],
                $data
            );
            $setting->setData($data);
        }
    }

    public function onSettingUpdate(SettingTypeField $field, Setting $setting, PreUpdateEventArgs $args)
    {
        $fieldType = $this->getFieldType($field->getType());
        if (method_exists($fieldType, 'onUpdate')) {
            $data = $setting->getData();
            $fieldType->onUpdate(
                $field,
                $setting,
                $args->getObjectManager()->getRepository('UniteCMSCoreBundle:Setting'),
                $args->getOldValue('data'),
                $data
            );
            $setting->setData($data);
        }
    }

    public function onContentRemove(ContentTypeField $field, Content $content, LifecycleEventArgs $args)
    {
        $fieldType = $this->getFieldType($field->getType());

        if (method_exists($fieldType, 'onSoftDelete') && !$content->getDeleted()) {
            $fieldType->onSoftDelete(
                $field,
                $content,
                $args->getObjectManager()->getRepository('UniteCMSCoreBundle:Content'),
                $content->getData()
            );
        }

        if (method_exists($fieldType, 'onHardDelete') && $content->getDeleted()) {
            $fieldType->onHardDelete(
                $field,
                $content,
                $args->getObjectManager()->getRepository('UniteCMSCoreBundle:Content'),
                $content->getData()
            );
        }
    }

    /**
     * @param FieldTypeInterface $fieldType
     *
     * @return FieldTypeManager
     */
    public function registerFieldType(FieldTypeInterface $fieldType)
    {
        if (!isset($this->fieldTypes[$fieldType::getType()])) {
            $this->fieldTypes[$fieldType::getType()] = $fieldType;
        }

        return $this;
    }
}
