<?php

namespace UniteCMS\VariantsFieldBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class VariantsFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_variants';
    }
}
