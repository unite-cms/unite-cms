<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\FormFactoryInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class FieldableFormBuilder
{
    private $formFactory;
    private $fieldTypeManager;

    public function __construct(FormFactoryInterface $formFactory, FieldTypeManager $fieldTypeManager)
    {
        $this->formFactory = $formFactory;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    public function createForm(Fieldable $fieldable, FieldableContent $content = null, $options = [])
    {
        $data = [];

        // Set all possible locales from fieldable.
        $options['locales'] = $fieldable->getLocales();

        if (!empty($content) && $content->getLocale()) {
            $data['locale'] = $content->getLocale();
        }

        $options['fields'] = [];

        // pass full FieldableContent object as option
        $options['content'] = $content;

        foreach ($fieldable->getFields() as $fieldDefinition) {

            $fieldType = $this->fieldTypeManager->getFieldType($fieldDefinition->getType());

            // Add the definition of the current field to the options.
            $options['fields'][] = new FieldableFormField($fieldType, $fieldDefinition);

            // If this fieldable content is new, allow field types to set default values.
            if(!$content || $content->isNew()) {
                $data[$fieldDefinition->getIdentifier()] = $fieldType->getDefaultValue($fieldDefinition);
            }

            /**
             * Add any value found for the current field to the data array. If we just pass the data array to the
             * form, we could have problems with old data for deleted fields.
             */
            else if ($content && array_key_exists($fieldDefinition->getIdentifier(), $content->getData())) {
                $data[$fieldDefinition->getIdentifier()] = $content->getData()[$fieldDefinition->getIdentifier()];
            }

        }

        return $this->formFactory->create(FieldableFormType::class, $data, $options);
    }

    /**
     * Alter data for fieldable content objects.
     *
     * This method allows all fields to hook into this process and alter data after form submit but before validation.
     *
     * @param FieldableContent $content
     * @param array $data
     * @return array
     */
    public function alterFieldableContentData(FieldableContent $content, array $data = []) : array {

        if (isset($data['locale'])) {
            $content->setLocale($data['locale']);
            unset($data['locale']);
        }

        // Allow all fields to alter data before we set it to the content object.
        if(!empty($content->getEntity()) && $content->getEntity() instanceof Fieldable) {
            foreach ($content->getEntity()->getFields() as $field) {
                $this->fieldTypeManager->alterFieldData($field, $data, $content);
            }
        }

        return $data;
    }

    /**
     * Assigns (form) data to a fieldable content object.
     *
     * @param FieldableContent $content
     * @param array $data
     */
    public function assignDataToFieldableContent(FieldableContent $content, array $data = []) {

        // Alter content data and allow fields to alter data.
        $data = $this->alterFieldableContentData($content, $data);

        // Set the data to the content object.
        if($content->getData() != $data) {
            $content->setData($data);
        }
    }
}
