<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebComponentType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('tag');
        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['tag'] = $options['tag'] ?? 'undefined-tag';
        $view->vars['tag'] = strtolower(trim($view->vars['tag']));
        $view->vars['tag'] = str_replace('_', '-', $view->vars['tag']);
        $view->vars['tag'] = preg_replace("/[^a-z0-9-]+/", "", $view->vars['tag']);

        if (empty($form->getData()) && !empty($options['empty_data'])) {
            $view->vars['value'] = $options['empty_data'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_web_component';
    }
}
