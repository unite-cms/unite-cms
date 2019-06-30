<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-11-30
 * Time: 13:31
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldExceptionFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['exception'] = $options['exception'] ?? null;
        parent::buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['exception']);
        parent::configureOptions($resolver);
    }

    public function getBlockPrefix()
    {
        return 'unite_cms_core_field_exception';
    }
}