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
use Symfony\Component\OptionsResolver\OptionsResolver;

class UniteCMSCoreTypeExtension extends AbstractTypeExtension
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // set default value for field
        /*if (isset($options['initial_data']) && $options['initial_data']) {

            $default = $options['initial_data'];

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($default) {

                $data = $event->getData();
                $content = $event->getForm()->getRoot()->getConfig()->getOption('content');

                // if new object and data is empty
                if ($content && $content->isNew() && empty($data)) {
                    $event->setData($default);
                }

            });

        }*/


        // add required validation dynamically
        /*if (isset($options['not_empty']) && $options['not_empty']) {

            $options['required'] = true;

            #dump($options['label']);
            #dump($options['required']);

            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

                $data = $event->getData();
                $form = $event->getForm();

                $event->getForm()->isEmpty()

                dump($event->getForm()->getName());
                dump($event->getForm()->getConfig()->getOption('required'));

                #if (empty($data)) {
                    $form->addError(new FormError('', 'not_blank'));
                #}

            });

        }*/

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

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        #$resolver->setNormalizer('')
        $resolver->setDefined('description');
        $resolver->setDefined('default');
        $resolver->setDefined('not_empty');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

}