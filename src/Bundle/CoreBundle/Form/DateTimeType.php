<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType as BaseDateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeType extends BaseDateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefined(['min', 'max', 'step']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        if (!empty($options['min'])) {
            $view->vars['attr']['min'] = $options['min'];
        }

        if (!empty($options['max'])) {
            $view->vars['attr']['max'] = $options['max'];
        }

        if (!empty($options['step'])) {
            $view->vars['attr']['step'] = $options['step'];
        }
    }
}
