<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColourPickerType extends WebComponentType implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_colour_picker';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WebComponentType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addModelTransformer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['assets'] = [
            [ 'css' => 'main.css', 'package' => 'UniteCMSCoreBundle' ],
            [ 'js' => 'main.js', 'package' => 'UniteCMSCoreBundle' ],
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'tag' => 'unite-cms-colour-picker-field',
                'empty_data' => '',
                'compound' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }
}
