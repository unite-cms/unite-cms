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
use Symfony\Component\VarDumper\Tests\Fixture\DumbFoo;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Expression\ContentExpressionChecker;

class AutoTextType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['expression']);
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

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

            if(isset($event->getData()['auto']) && ($event->getData()['auto'] == true || $event->getData()['auto'] == 'on')) {

                // If this is a fieldable content form, use the content object and the defined expression to generate the auto value.
                if($event->getForm()->getRoot()->getConfig()->hasOption('content')) {

                    $content = $event->getForm()->getRoot()->getConfig()->getOption('content');
                    $auto_value = ($event->getData()['auto'] === true || $event->getData()['auto'] === 'on') ? true : false;

                    if(empty($content->getId()) || $event->getForm()->getConfig()->getOption('auto_update') || $event->getForm()->getData()['auto'] != $auto_value) {

                        // Get currently submitted data
                        // TODO: WE NEED TO GET THE CURRENT SUBMITTED DATA. THIS APPROACH HERE IS NOT WORKING!
                        $contentData = $content->getData();
                        foreach($contentData as $key => $value) {
                            $contentData[$key] = $event->getForm()->getRoot()->has($key) ?
                                $event->getForm()->getRoot()->get($key)->getData() :
                                $contentData[$key] = $value;
                        }

                        $expressionChecker = new ContentExpressionChecker();
                        $event->setData([
                            'auto' => true,
                            'text' => $expressionChecker->evaluate(
                                $event->getForm()->getConfig()->getOption('expression'),
                                $event->getForm()->getRoot()->getConfig()->getOption('content'),
                                $contentData
                            ),
                        ]);
                    } else {

                        // Set original data
                        $event->setData([
                            'auto' => true,
                            'text' => $event->getForm()->getData()['text']
                        ]);
                    }
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
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_auto_text';
    }
}
