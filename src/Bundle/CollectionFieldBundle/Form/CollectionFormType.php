<?php

namespace UniteCMS\CollectionFieldBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CollectionFormType extends CollectionType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event){

            $data = $event->getData();

            if(!is_array($data)) {
                return;
            }

            $keys = array_keys($data);
            $values = array_values($data);
            $formRows = [];

            $event->setData($values);

            // Replace custom named form rows with delta named ones. This is a similar approach as ResizeFormListener does it.
            foreach($keys as $row) {
                if($event->getForm()->has($row)) {
                    $formRows[] = $event->getForm()->get($row);
                    $event->getForm()->remove($row);
                }
            }

            // Now we add all previously removed form rows again, but we use delta as name.
            foreach($formRows as $delta => $row) {
                $type = $row->getConfig()->getType()->getInnerType();
                $options = $row->getConfig()->getOptions();
                $options['property_path'] = '['.$delta.']';
                $event->getForm()->add($delta, get_class($type), $options);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        // In order to be able to have required child elements (see Symfony\Component\Form\Form::isRequired()), we
        // set the collection form type to required. Here we undo this to avoid a * in the label. At this point,
        // however the children are already built, so any required fields are already marked as required.
        $view->vars['required'] = false;

        $view->vars['tag'] = 'unite-cms-collection-field';
        $view->vars['assets'] = [
            ['css' => 'main.css', 'package' => 'UniteCMSCollectionFieldBundle'],
            ['js' => 'main.js', 'package' => 'UniteCMSCollectionFieldBundle'],
        ];

        if(!empty($view->vars['prototype'])) {
            $this->mergeChildAssets($view->vars['prototype'], $view->vars['assets']);
        }
    }

    private function mergeChildAssets(FormView $view, &$assets) {
        if(!empty($view->vars['assets'])) {
            $assets = array_merge($assets, $view->vars['assets']);
        }

        foreach($view->children as $formView) {
            $this->mergeChildAssets($formView, $assets);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
