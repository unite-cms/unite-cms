<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 15:07
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReferenceType extends WebComponentType implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_reference';
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
        $builder->add('domain', HiddenType::class);
        $builder->add('content_type', HiddenType::class);
        $builder->add('content', HiddenType::class);

        $builder->addModelTransformer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        if(!empty($options['assets']) && is_array($options['assets'])) {
            $view->vars['assets'] = $options['assets'];
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'compound' => true,
                'error_bubbling' => false,
                'tag' => 'unite-cms-core-reference-field',
                'assets' => [],
                'empty_data' => [
                    'domain' => null,
                    'content_type' => null,
                    'content' => null,
                ],
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
        if (empty($value) || empty($value['content'])) {
            return null;
        }

        return $value;
    }
}
