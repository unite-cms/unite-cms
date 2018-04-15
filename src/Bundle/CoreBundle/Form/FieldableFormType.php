<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class FieldableFormType extends AbstractType
{
    /**
     * @var TokenStorage $tokenStorage
     */
    private $tokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Handle content locales
        if (!empty($options['locales'])) {

            // if this fieldable has exactly one possible locale, add it as hidden field.
            if (count($options['locales']) == 1) {
                $builder->add('locale', HiddenType::class, ['data' => $options['locales'][0]]);

                // if this fieldable has more than one possible locale, render a selection list.
            } else {
                $choices = [];
                foreach ($options['locales'] as $locale) {
                    $choices[Intl::getLocaleBundle()->getLocaleName($locale)] = $locale;
                }
                $builder->add('locale', ChoiceType::class, ['choices' => $choices]);
            }
        }

        /**
         * @var FieldableFormField $field
         */
        foreach ($options['fields'] as $field) {
            $builder->add(
                $field->getFieldType()->getIdentifier($field->getFieldDefinition()),
                $field->getFieldType()->getFormType($field->getFieldDefinition()),
                $field->getFieldType()->getFormOptions($field->getFieldDefinition())
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('fields');
        $resolver->setDefined('locales');
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getProviderKey() == "api") {
            $resolver->setDefault('csrf_protection', false);
        }
    }
}
