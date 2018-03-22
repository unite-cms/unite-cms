<?php

namespace UnitedCMS\CoreBundle\Form;

use Symfony\Component\Form\FormFactory;
use UnitedCMS\CoreBundle\Entity\Fieldable;
use UnitedCMS\CoreBundle\Entity\FieldableContent;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class FieldableFormBuilder
{
    /**
     * @var TokenStorage $securityTokenStorage
     */
    private $securityTokenStorage;

    private $formFactory;
    private $fieldTypeManager;

    public function __construct(FormFactory $formFactory, FieldTypeManager $fieldTypeManager, TokenStorage $tokenStorage)
    {
        $this->formFactory = $formFactory;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->securityTokenStorage = $tokenStorage;
    }

    /**
     * determine if the current authentication is done via API token or not, disable csrf for API Token
     *
     * @param $options
     *
     * @return array
     */
    public function handleCsrfProtection($options)
    {
        if ($this->securityTokenStorage->getToken() && $this->securityTokenStorage->getToken()->getProviderKey() == "api")
        {
            $options['csrf_protection'] = false;
        }
        return $options;
    }

    public function createForm(Fieldable $fieldable, FieldableContent $content = null, $options = [])
    {
        $data = [];

        $options = $this->handleCsrfProtection($options);

        // Set all possible locales from fieldable.
        $options['locales'] = $fieldable->getLocales();

        if(!empty($content) && $content->getLocale()) {
            $data['locale'] = $content->getLocale();
        }

        $options['fields'] = [];

        foreach ($fieldable->getFields() as $fieldDefinition) {

            // Add the definition of the current field to the options.
            $options['fields'][] = new FieldableFormField(
                $this->fieldTypeManager->getFieldType($fieldDefinition->getType()),
                $fieldDefinition
            );

            /**
             * Add any value found for the current field to the data array. If we just pass the data array to the
             * form, we could have problems with old data for deleted fields.
             */
            if($content && array_key_exists($fieldDefinition->getIdentifier(), $content->getData())) {
                $data[$fieldDefinition->getIdentifier()] = $content->getData()[$fieldDefinition->getIdentifier()];
            }
        }

        return $this->formFactory->create(FieldableFormType::class, $data, $options);
    }
}