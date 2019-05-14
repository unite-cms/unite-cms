<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 02.11.18
 * Time: 14:36
 */

namespace UniteCMS\CoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolation;

class UniteCMSCoreTypeExtension extends AbstractTypeExtension
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // add required validation dynamically
        if (isset($options['not_empty']) && $options['not_empty']) {
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                if($event->getForm()->isEmpty()) {
                    $error = new FormError($this->translator->trans('not_blank', [], 'validators'), null, [], null, new ConstraintViolation(
                        $this->translator->trans('not_blank', [], 'validators'),
                        null,
                        [],
                        null,
                        'data'.$event->getForm()->getPropertyPath(),
                        ''
                    ));
                    $event->getForm()->addError($error);
                }
            });
        }

    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // pass description to template
        if (isset($options['description'])) {
            $view->vars['description'] = $options['description'];
        }

        if (isset($options['form_group']) && $options['form_group'] !== null) {
            if(filter_var($options['form_group'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) {
                $view->vars['form_group'] = false;
            } elseif(is_string($options['form_group'])) {
                $view->vars['form_group'] = $options['form_group'];
            } else {
                unset($view->vars['form_group']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('description');
        $resolver->setDefined('default');
        $resolver->setDefined('not_empty');
        $resolver->setDefined('form_group');

        // If not_empty is set, also set the required option to true
        $resolver->setNormalizer('required', function(Options $options, $value){
            return $options->offsetExists('not_empty') && $options->offsetGet('not_empty') ? true : $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    static public function getExtendedTypes()
    {
        return [ FormType::class ];
    }

}