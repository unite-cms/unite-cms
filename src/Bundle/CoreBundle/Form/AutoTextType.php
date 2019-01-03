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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Event\FieldableFormEvent;
use UniteCMS\CoreBundle\Expression\ContentExpressionChecker;

class AutoTextType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['expression', 'validation_url']);
        $resolver->setDefaults([
            'compound' => true,
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

        $propertyPath = $builder->getPropertyPath();

        $builder->addEventListener(FieldableFormType::FIELDABLE_FORM_SUBMIT, function(FieldableFormEvent $event) use ($propertyPath) {

            // If auto is set to true
            if(!empty($event->getData()['auto'])) {

                $fieldableForm = $event->getForm()->getRoot();

                // If this is a fieldable content form with a set content object.
                if($fieldableForm->getConfig()->hasOption('content')) {

                    /**
                     * @var Content $content
                     */
                    $content = $fieldableForm->getConfig()->getOption('content');
                    $prevData = empty($event->getForm()->getParent()->getData()[$event->getForm()->getName()]) ? null : $event->getForm()->getParent()->getData()[$event->getForm()->getName()];
                    $textValue = empty($prevData['text']) ? '' : $prevData['text'];

                    // If content is new or auto_update is set to true or prev. auto value was false, we update value
                    if(empty($content->getId()) || $event->getForm()->getConfig()->getOption('auto_update') || empty($prevData) || empty($prevData['auto'])) {

                        $expressionChecker = new ContentExpressionChecker();
                        $textValue = $expressionChecker->evaluate(
                            $event->getForm()->getConfig()->getOption('expression'),
                            $content,
                            $event->getFieldableData());

                    }

                    // Set new textValue or reset to old text value if we don't allow update here.
                    $event->setData(['auto' => true, 'text' => $textValue]);
                }

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
        $view->vars['validation_url'] = $options['validation_url'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_auto_text';
    }
}
