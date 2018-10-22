<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\FormFactory;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class FieldableFormBuilder
{
    private $formFactory;
    private $fieldTypeManager;

    public function __construct(FormFactory $formFactory, FieldTypeManager $fieldTypeManager)
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

        foreach ($fieldable->getFields() as $fieldDefinition) {

            $settings = $fieldDefinition->getSettings();

            // Add the definition of the current field to the options.
            $options['fields'][] = new FieldableFormField(
                $this->fieldTypeManager->getFieldType($fieldDefinition->getType()),
                $fieldDefinition
            );

            /**
             * Add any value found for the current field to the data array. If we just pass the data array to the
             * form, we could have problems with old data for deleted fields.
             */
            if ($content && array_key_exists($fieldDefinition->getIdentifier(), $content->getData())) {
                $data[$fieldDefinition->getIdentifier()] = $content->getData()[$fieldDefinition->getIdentifier()];
            }

            /**
             *  check if empty_data isset and pre-populate fields only on new created content,
             *  data option would always override the value and empty_data does not pre-populate the field
             */
            if (isset($settings->empty_data) && empty($data[$fieldDefinition->getIdentifier()])) {
                $data[$fieldDefinition->getIdentifier()] = $settings->empty_data;
            }

        }

        return $this->formFactory->create(FieldableFormType::class, $data, $options);
    }
}
