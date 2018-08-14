<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 10.05.18
 * Time: 14:36
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use UniteCMS\CoreBundle\Form\Model\ChoiceCardOption;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceCardsType extends ChoiceType implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_choice_cards';
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
        $view->vars['compact'] = $options['compact'];

        if($view->vars['compact']) {
            if(!isset($view->vars['attr']['class'])) {
                $view->vars['attr']['class'] = '';
            }
            $view->vars['attr']['class'] .= 'compact';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('expanded', true);
        $resolver->setDefault('compact', false);
        $resolver->setDefault('choice_value', function ($value) {
            if($value instanceof ChoiceCardOption) {
                return $value->getValue();
            }
            return $value;
        });
        $resolver->setDefault('choice_label', function ($value, $key, $index) {
            if($value instanceof ChoiceCardOption) {
                return $value->getLabel();
            }
            return $value;
        });
        $resolver->setDefault('choice_attr', function ($value, $key, $index) {
            if($value instanceof ChoiceCardOption) {
                return [
                    'icon' => $value->getIcon(),
                    'description' => $value->getDescription(),
                ];
            }
            return [];
        });
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
        if($value instanceof ChoiceCardOption) {
            return $value->getValue();
        }

        return $value;
    }
}