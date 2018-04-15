<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebComponentType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('tag');
        $resolver->setDefaults([
            'compound' => false,
            'label' => false,
        ]);
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

        $view->vars['value'] = $this->transform($view->vars['value']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_web_component';
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        if (!is_string($data) && null !== $data) {
            return json_encode($data);
        }

        // Model data should not be transformed
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (empty($data)) {
            return null;
        }

        return null === $data ? '' : $data;
    }
}
