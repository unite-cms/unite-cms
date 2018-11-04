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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UniteCMSCoreFieldTypeExtension extends AbstractTypeExtension
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        dump($options);


        // add required constraint
        if (isset($options['required']) && $options['required']) {

            #$options['constraints'][] = new NotBlank();

        }

        // set default values for field
        if (isset($options['initial_data']) && $options['initial_data']) {

            //dump("required");
        }


    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // pass description
        if (isset($options['description'])) {
            $view->vars['description'] = $options['description'];
        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('description', '');
        $resolver->setDefault('initial_data', '');
        $resolver->setDefined('content');
        $resolver->setDefault('required', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}