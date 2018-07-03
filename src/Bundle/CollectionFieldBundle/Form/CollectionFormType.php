<?php

namespace UniteCMS\CollectionFieldBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CollectionFormType extends CollectionType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
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
