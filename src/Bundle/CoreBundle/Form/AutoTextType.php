<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 14:19
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AutoTextType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'expression' => '',
            'label_alternative' => 'Manual',
            'text_widget' => TextType::class,
            'auto_update' => false,
        ]);
    }

    /**
     * Text widget can only be text or textarea.
     *
     * @param string $type
     * @return string
     */
    private function normalizeWidgetType(string $type) : string {
        if(empty($type) || !in_array($type, [TextType::class, TextareaType::class])) {
            $type = TextType::class;
        }
        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', $this->normalizeWidgetType($options['text_widget']), ['label' => $options['label'], 'not_empty' => $options['not_empty']])
            ->add('auto', CheckboxType::class, ['label' => $options['label']]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if(!empty($event->getData()['auto'])) {
                $event->setData([
                    'auto' => true,
                    'text' => 'TODO From AutoTextType Form',
                ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['widget_type'] = $this->normalizeWidgetType($options['text_widget']);
        $view->vars['label_alternative'] = $options['label_alternative'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_auto_text';
    }
}
