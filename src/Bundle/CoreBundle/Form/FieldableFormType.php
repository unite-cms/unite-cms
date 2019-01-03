<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use UniteCMS\CoreBundle\Event\FieldableFormEvent;

class FieldableFormType extends AbstractType
{
    const FIELDABLE_FORM_PRE_SUBMIT = 'unite.fieldable.form.pre_submit';
    const FIELDABLE_FORM_SUBMIT = 'unite.fieldable.form.submit';

    /**
     * @var TokenStorage $tokenStorage
     */
    private $tokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Recursively dispatch a form event to all children. This allows us to dispatch form events AFTER all fields have
     * been initialized / normalized etc.
     *
     * @param FormInterface $form
     * @param FormEvent $event
     * @param string $event_type
     */
    private static function recursivelyDispatchSubmitEvent(FormInterface $form, FormEvent $event, string $event_type) {

        foreach($form->all() as $key => $child) {

            $childEvent = new FieldableFormEvent($child, $child->getData(), $event->getData());

            if($child->getConfig()->getEventDispatcher()->hasListeners($event_type)) {
                $child->getConfig()->getEventDispatcher()->dispatch($event_type, $childEvent);

                // Allow event listeners to override data for the current form type.
                $eventData = $event->getData();
                $eventData[$key] = $childEvent->getData();
                $event->setData($eventData);
            }
            static::recursivelyDispatchSubmitEvent($child, $event, $event_type);
        }
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
            try {
                $builder->add(
                    $field->getFieldType()->getIdentifier($field->getFieldDefinition()),
                    $field->getFieldType()->getFormType($field->getFieldDefinition()),
                    $field->getFieldType()->getFormOptions($field->getFieldDefinition())
                );
            } catch (\Exception $e) {
                $builder->add(
                    $field->getFieldType()->getIdentifier($field->getFieldDefinition()),
                    FieldExceptionFormType::class
                );
            }
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event){
            static::recursivelyDispatchSubmitEvent($event->getForm(), $event, static::FIELDABLE_FORM_PRE_SUBMIT);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event){
            static::recursivelyDispatchSubmitEvent($event->getForm(), $event, static::FIELDABLE_FORM_SUBMIT);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('fields');
        $resolver->setDefined('locales');
        $resolver->setDefined('content');

        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getProviderKey() == "api") {
            $resolver->setDefault('csrf_protection', false);
        }
    }
}
